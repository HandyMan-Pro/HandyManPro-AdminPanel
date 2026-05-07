<?php

namespace App\Http\Resources\API;

use App\Models\Setting;
use App\Traits\TranslationTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    use TranslationTrait;



public function toArray(Request $request): array
{
    if (!$this->resource) {
        return [];
    }

    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $sitesetupValue = json_decode(optional($sitesetup)->value ?? '{}');
    $targetTimezone = isset($sitesetupValue->time_zone) ? trim((string) $sitesetupValue->time_zone) : 'UTC';
    $timeFormat = $sitesetupValue->time_format ?? 'H:i';
    $sourceTimezone = 'UTC';

    $convertFromRawUtc = function ($raw) use ($sourceTimezone, $targetTimezone, $timeFormat) {
        if (empty($raw)) {
            return null;
        }
        try {
            return Carbon::parse($raw, $sourceTimezone)->setTimezone($targetTimezone)->format($timeFormat);
        } catch (\Exception $e) {
            return null;
        }
    };

    $headerValue = $request->header('language-code') ?? session()->get('locale', 'en');
    
    // Build translations array similar to ServiceResource
    $translation = json_encode(
        $this->translations
            ->groupBy('locale')
            ->mapWithKeys(function ($items, $locale) {
                return [
                    $locale => $items->pluck('value', 'attribute')->toArray()
                ];
            })
    );
    
    $englishTranslation = [
        'shop_name' => $this->shop_name,
    ];

    // Decode existing translations JSON for modification
    $translationsArray = $translation !== '[]' && $translation ? json_decode($translation, true) : [];

    // Merge `en` translations
    $translationsArray['en'] = $englishTranslation;

    // Encode back to JSON
    $finalTranslation = json_encode($translationsArray);

    // If provider_id is in request, show all services; otherwise only active services
    $services = $request->has('provider_id') 
        ? $this->services 
        : $this->services->where('status', 1);

    return [
        'id' => $this->id,
        'name' => $this->getTranslation($this->translations, $headerValue, 'shop_name', $this->shop_name) ?? $this->shop_name,
        'country_name' => optional($this->country)->name ?? null,
        'state_name' => optional($this->state)->name ?? null,
        'city_name' => optional($this->city)->name ?? null,
        'address' => $this->address,
        'latitude' => $this->lat,
        'longitude' => $this->long,

        // Convert times using raw DB values (stored in UTC)
        'shop_start_time' => $convertFromRawUtc($this->getRawOriginal('shop_start_time')),
        'shop_end_time' => $convertFromRawUtc($this->getRawOriginal('shop_end_time')),

        'contact_number' => $this->contact_number,
        'email' => $this->email,
        'shop_image' => getAttachments($this->getMedia('shop_attachment')),
        'services_count' => $services->count() ?? 0,
        'services' => $services->take(3)->values()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
            ];
        }),
        'provider_name' => optional($this->provider)->display_name,
        'provider_image' => getSingleMedia($this->provider, 'profile_image', null),
        'shop_hours' => ShopHourResource::collection(
            $this->shopHours->sortBy(function ($hour) {
                $dayOrder = [
                    'monday' => 1,
                    'tuesday' => 2,
                    'wednesday' => 3,
                    'thursday' => 4,
                    'friday' => 5,
                    'saturday' => 6,
                    'sunday' => 7,
                ];
                return $dayOrder[strtolower($hour->day)] ?? 8;
            })->values()
        ),
        'translations' => ($finalTranslation === '[]' || !$finalTranslation) ? null : $finalTranslation,
        'is_active' => $this->is_active
    ];
  }

}
