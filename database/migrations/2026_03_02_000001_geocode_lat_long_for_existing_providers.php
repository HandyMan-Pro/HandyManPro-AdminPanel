<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocode Lat/Long for Existing Providers
 * ─────────────────────────────────────────────────────────────────────────────
 * This migration reads the `address` column of every active provider row that
 * still has NULL (or zero) latitude / longitude and calls the Google Geocoding
 * API to resolve coordinates.
 *
 * Pre-requisites
 * ──────────────
 *  1. The columns `latitude` and `longitude` must already exist on `users`.
 *     (Added by: 2026_02_24_111312_add_lat_long_to_users_table.php)
 *  2. A valid Google Maps Geocoding API key must be set in site settings or .env:
 *     - Settings table (preferred): site-setup key with google_maps_key
 *     - Environment variable: GOOGLE_MAPS_API_KEY=AIza...
 *  3. The key must have the "Geocoding API" product enabled in Google Cloud
 *     Console and billing must be active on the project.
 *
 * Run Command
 * ───────────
 *  php artisan migrate
 *  → If API key is not found in settings or .env, migration skips geocoding
 *    but continues successfully (no .env dependency).
 *
 * Re-run safely
 * ─────────────
 *  The migration only touches rows where latitude IS NULL OR latitude = 0.
 *  Already-geocoded providers are skipped automatically.
 *
 * Rollback
 * ────────
 *  php artisan migrate:rollback
 *  → sets latitude and longitude back to NULL for ALL providers.
 *    Only do this if you want to fully wipe geocoded coordinates.
 */
return new class extends Migration
{
    /** Seconds to wait between API calls (respect Google's rate limit). */
    private const RATE_LIMIT_SLEEP = 0.05; // 50 ms → ~20 req/s (well within 50 req/s limit)

    /** Number of retries on transient HTTP errors. */
    private const MAX_RETRIES = 3;

    public function up(): void
    {
        // Try to get API key from settings table first (site setup)
        $apiKey = $this->getGoogleMapsApiKeyFromSettings();

        // Fall back to environment variable
        if (empty($apiKey)) {
            $apiKey = config('services.google_maps.key') ?? env('GOOGLE_MAPS_API_KEY');
        }

        // If still not found, log warning and skip geocoding (no .env dependency)
        if (empty($apiKey)) {
            $message = '[GeocodeProviders] Google Maps API key not found in settings or .env. Skipping geocoding.';
            Log::warning($message);
            return;
        }

        try {
            // Fetch providers that have an address but no coordinates
            $providers = DB::table('users')
                ->where('user_type', 'provider')
                ->where('status', 1)
                ->whereNotNull('address')
                ->where('address', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('latitude')
                      ->orWhere('latitude', '0')
                      ->orWhere('latitude', 0);
                })
                ->select('id', 'address')
                ->get();

            $total   = $providers->count();
            $success = 0;
            $failed  = 0;
            $skipped = 0;

            Log::info("[GeocodeProviders] Starting geocoding for {$total} providers.");

            foreach ($providers as $provider) {
                $address = trim($provider->address);

                if (empty($address)) {
                    $skipped++;
                    continue;
                }

                $coords = $this->geocodeWithRetry($address, $apiKey);

                if ($coords) {
                    DB::table('users')
                        ->where('id', $provider->id)
                        ->update([
                            'latitude'   => $coords['lat'],
                            'longitude'  => $coords['lng'],
                            'updated_at' => now(),
                        ]);
                    $success++;
                    Log::info("[GeocodeProviders] ✓ Provider #{$provider->id} → {$coords['lat']}, {$coords['lng']}");
                } else {
                    $failed++;
                    Log::warning("[GeocodeProviders] ✗ Failed to geocode Provider #{$provider->id} (address: \"{$address}\")");
                }

                // Respect Google rate limit
                usleep((int)(self::RATE_LIMIT_SLEEP * 1_000_000));
            }

            Log::info("[GeocodeProviders] Done. Total={$total} Success={$success} Failed={$failed} Skipped={$skipped}");

        } catch (\Throwable $e) {
            $errorMsg = "Migration failed with error: {$e->getMessage()}";
            Log::error("[GeocodeProviders] {$errorMsg} at {$e->getFile()}:{$e->getLine()}");
            $this->warn("[GeocodeProviders] Error occurred. Rolling back migration...");

            // Execute rollback to undo any partial changes
            $this->down();

            // Re-throw exception to mark migration as failed
            throw new \Exception($errorMsg, 0, $e);
        }
    }

    public function down(): void
    {
        // Wipe geocoded coordinates from all providers
        DB::table('users')
            ->where('user_type', 'provider')
            ->update([
                'latitude'   => null,
                'longitude'  => null,
                'updated_at' => now(),
            ]);

        Log::info('[GeocodeProviders] Rollback: cleared latitude/longitude for all providers.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get Google Maps API key from settings table.
     * Looks in the site-setup settings for 'google_maps_key' field.
     *
     * @return string|null
     */
    private function getGoogleMapsApiKeyFromSettings(): ?string
    {
        try {
            // Try to get from site-setup settings
            $siteSetup = DB::table('settings')
                ->where('type', 'site-setup')
                ->first();

            if ($siteSetup && $siteSetup->value) {
                $settings = json_decode($siteSetup->value, true);
                if (is_array($settings) && !empty($settings['google_maps_key'])) {
                    return $settings['google_maps_key'];
                }
            }

            // Try to get from general-setting
            $generalSetting = DB::table('settings')
                ->where('type', 'general-setting')
                ->first();

            if ($generalSetting && $generalSetting->value) {
                $settings = json_decode($generalSetting->value, true);
                if (is_array($settings) && !empty($settings['google_maps_key'])) {
                    return $settings['google_maps_key'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[GeocodeProviders] Error reading settings table: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Call the Google Geocoding API with automatic retry on transient errors.
     *
     * @return array{lat: float, lng: float}|null
     * @throws \Exception If billing is not enabled or API key is invalid
     */
    private function geocodeWithRetry(string $address, string $apiKey): ?array
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            try {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key'     => $apiKey,
                ]);

                if (!$response->ok()) {
                    Log::warning("[GeocodeProviders] HTTP {$response->status()} for address: \"{$address}\" (attempt {$attempt})");
                    usleep(500_000); // wait 0.5s before retry
                    continue;
                }

                $json   = $response->json();
                $status = $json['status'] ?? 'UNKNOWN';

                // Billing/quota errors — should fail immediately and trigger rollback
                if (in_array($status, ['REQUEST_DENIED', 'OVER_DAILY_LIMIT', 'OVER_QUERY_LIMIT'])) {
                    $errorMsg = match ($status) {
                        'REQUEST_DENIED' => 'Google Maps API key is invalid or billing is not enabled. Please enable billing in Google Cloud Console.',
                        'OVER_DAILY_LIMIT' => 'Google Maps API daily quota exceeded. Billing limit may not be enabled or quota needs increase.',
                        'OVER_QUERY_LIMIT' => 'Google Maps API query rate limit exceeded. Please enable billing or increase quota.',
                        default => "Google Maps API error: {$status}"
                    };
                    Log::error("[GeocodeProviders] CRITICAL: {$errorMsg}");
                    throw new \Exception($errorMsg);
                }

                // Other permanent failures — skip this address but continue
                if (in_array($status, ['ZERO_RESULTS', 'INVALID_REQUEST'])) {
                    Log::warning("[GeocodeProviders] API status={$status} for address: \"{$address}\"");
                    return null;
                }

                if ($status === 'OK' && !empty($json['results'][0]['geometry']['location'])) {
                    $loc = $json['results'][0]['geometry']['location'];
                    return ['lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng']];
                }

                // UNKNOWN_ERROR is transient — retry
                usleep(500_000);

            } catch (\Exception $e) {
                // Re-throw critical billing/authentication errors immediately
                $messageUpper = strtoupper($e->getMessage());
                if (
                    str_contains(strtolower($e->getMessage()), 'billing') ||
                    str_contains($messageUpper, 'REQUEST_DENIED') ||
                    str_contains($messageUpper, 'OVER_DAILY_LIMIT') ||
                    str_contains($messageUpper, 'OVER_QUERY_LIMIT')
                ) {
                    throw $e;
                }
                Log::error("[GeocodeProviders] Exception on attempt {$attempt}: " . $e->getMessage());
                usleep(500_000);
            }
        }

        return null;
    }

    /** Output to console if running via artisan (no-op otherwise). */
    private function warn(string $message): void
    {
        if (app()->runningInConsole()) {
            fwrite(STDERR, $message . PHP_EOL);
        }
    }
};
