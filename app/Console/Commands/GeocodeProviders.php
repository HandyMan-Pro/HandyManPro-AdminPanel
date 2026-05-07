<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocode lat/long for providers that are missing coordinates.
 *
 * Usage:
 *   php artisan providers:geocode
 *
 * Options:
 *   --force     Also re-geocode providers that already have coordinates
 *   --id=12     Only process a single provider by ID (useful for spot-fixing)
 *   --dry-run   Show what would be updated without making any DB changes
 */
class GeocodeProviders extends Command
{
    protected $signature = 'providers:geocode
                            {--force    : Re-geocode even providers that already have coordinates}
                            {--id=      : Process only a specific provider ID}
                            {--dry-run  : Show results without saving to the database}';

    protected $description = 'Geocode latitude/longitude for providers using their address via Google Maps API';

    /** Seconds between API calls (50ms → ~20 req/s, well within Google limits). */
    private const RATE_LIMIT_SLEEP = 0.05;

    /** Retries on transient HTTP/network errors. */
    private const MAX_RETRIES = 3;

    public function handle(): int
    {
        $apiKey = config('services.google_maps.key') ?? env('GOOGLE_MAPS_API_KEY');

        if (empty($apiKey)) {
            $this->error('GOOGLE_MAPS_API_KEY is not set in your .env file.');
            $this->line('  Add the following to your .env and run: php artisan config:clear');
            $this->line('  GOOGLE_MAPS_API_KEY=AIzaSy...');
            return self::FAILURE;
        }

        $force  = $this->option('force');
        $dryRun = $this->option('dry-run');
        $onlyId = $this->option('id');

        // ── Build query ───────────────────────────────────────────────────────
        $query = DB::table('users')
            ->where('user_type', 'provider')
            ->where('status', 1)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->select('id', 'address', 'latitude', 'longitude');

        if ($onlyId) {
            $query->where('id', (int) $onlyId);
        } elseif (!$force) {
            // Only providers still missing coordinates
            $query->where(function ($q) {
                $q->whereNull('latitude')
                  ->orWhere('latitude', '0')
                  ->orWhere('latitude', 0);
            });
        }

        $providers = $query->get();
        $total     = $providers->count();

        if ($total === 0) {
            $this->info('No providers need geocoding. All providers already have coordinates.');
            $this->line('Tip: use --force to re-geocode all providers, or --id=X to target one.');
            return self::SUCCESS;
        }

        $verb = $dryRun ? '[DRY-RUN] Would process' : 'Processing';
        $this->info("{$verb} {$total} provider(s)...");
        $this->newLine();

        $success = 0;
        $failed  = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($providers as $provider) {
            $address = trim($provider->address);

            if (empty($address)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $coords = $this->geocodeWithRetry($address, $apiKey);

            if ($coords) {
                if (!$dryRun) {
                    DB::table('users')
                        ->where('id', $provider->id)
                        ->update([
                            'latitude'   => $coords['lat'],
                            'longitude'  => $coords['lng'],
                            'updated_at' => now(),
                        ]);
                }
                $success++;
                Log::info("[GeocodeProviders] ✓ Provider #{$provider->id} → {$coords['lat']}, {$coords['lng']}");
            } else {
                $failed++;
                Log::warning("[GeocodeProviders] ✗ Failed Provider #{$provider->id} (address: \"{$address}\")");
            }

            $bar->advance();
            usleep((int)(self::RATE_LIMIT_SLEEP * 1_000_000));
        }

        $bar->finish();
        $this->newLine(2);

        // ── Summary table ─────────────────────────────────────────────────────
        $this->table(
            ['Total', 'Success', 'Failed', 'Skipped', 'Dry Run'],
            [[$total, $success, $failed, $skipped, $dryRun ? 'YES' : 'no']]
        );

        if ($failed > 0) {
            $this->warn("{$failed} provider(s) could not be geocoded.");
            $this->line('Common causes:');
            $this->line('  • REQUEST_DENIED   → API key not valid, Geocoding API not enabled, or billing not active');
            $this->line('  • ZERO_RESULTS     → Address is too vague or incorrect');
            $this->line('  • OVER_QUERY_LIMIT → Daily quota exceeded');
            $this->line('Check storage/logs/laravel-' . now()->format('Y-m-d') . '.log for details.');
        }

        if ($dryRun) {
            $this->info('Dry run complete. No changes were saved. Remove --dry-run to apply.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Call Google Geocoding API with automatic retry on transient errors.
     *
     * @return array{lat: float, lng: float}|null
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
                    usleep(500_000);
                    continue;
                }

                $json   = $response->json();
                $status = $json['status'] ?? 'UNKNOWN';

                // Permanent failures — no point retrying
                if (in_array($status, ['ZERO_RESULTS', 'INVALID_REQUEST', 'REQUEST_DENIED', 'OVER_DAILY_LIMIT', 'OVER_QUERY_LIMIT'])) {
                    Log::warning("[GeocodeProviders] API status={$status} for address: \"{$address}\"");
                    return null;
                }

                if ($status === 'OK' && !empty($json['results'][0]['geometry']['location'])) {
                    $loc = $json['results'][0]['geometry']['location'];
                    return ['lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng']];
                }

                // UNKNOWN_ERROR → transient, retry
                usleep(500_000);

            } catch (\Throwable $e) {
                Log::error("[GeocodeProviders] Exception on attempt {$attempt}: " . $e->getMessage());
                usleep(500_000);
            }
        }

        return null;
    }
}
