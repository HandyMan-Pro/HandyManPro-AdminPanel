<?php

namespace App\Http\Resources\API;

use App\Models\Setting;
use App\Traits\TranslationTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopDetailResource extends JsonResource
{
    use TranslationTrait;
    
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode($sitesetup->value);
        $timezone = $sitesetupValue->time_zone ?? 'UTC';
        $timeformate = $sitesetupValue->time_format ?? 'H:i';

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
        $showAllServices = $request->has('provider_id') && !empty($request->provider_id);
        $services = $showAllServices 
            ? $this->services 
            : $this->services->where('status', 1);

        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'name' => $this->getTranslation($this->translations, $headerValue, 'shop_name', $this->shop_name) ?? $this->shop_name,
            'country_id' => $this->country_id,
            'country_name' => optional($this->country)->name ?? null,
            'state_id' => $this->state_id,
            'state_name' => optional($this->state)->name ?? null,
            'city_id' => $this->city_id,
            'city_name' => optional($this->city)->name ?? null,
            'address' => $this->address,
            //    'shop_start_time' => $this->shop_start_time
            //     ? Carbon::parse($this->shop_start_time)->timezone($timezone)->format($timeformate)
            //     : null,
            // 'shop_end_time' => $this->shop_end_time
            //     ? Carbon::parse($this->shop_end_time)->timezone($timezone)->format($timeformate)
            //     : null,
            'shop_hour' => $this->shopHours->map(function ($hour) use ($timeformate) {
                return [
                    'id' => $hour->id,
                    'day' => $hour->day,
                    'start_time' => $hour->start_time ? Carbon::parse($hour->start_time)->format($timeformate) : null,
                    'end_time' => $hour->end_time ? Carbon::parse($hour->end_time)->format($timeformate) : null,
                    'is_holiday' => (bool) $hour->is_holiday,
                    'breaks' => collect($hour->breaks)->map(function ($break) use ($timeformate) {
                        return [
                            'start_break' => isset($break['start_break']) ? Carbon::parse($break['start_break'])->format($timeformate) : null,
                            'end_break' => isset($break['end_break']) ? Carbon::parse($break['end_break'])->format($timeformate) : null,
                        ];
                    }),
                    'created_at' => $hour->created_at,
                    'updated_at' => $hour->updated_at,
                    'deleted_at' => $hour->deleted_at,
                ];
            }),
            'latitude' => $this->lat,
            'longitude' => $this->long,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'shop_image' => getAttachments($this->getMedia('shop_attachment')),
            'services' => $services->values()->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'attchments' => getAttachments($service->getMedia('service_attachment')),
                    'discount_price' => $service->price - ($service->price * $service->discount / 100),
                    'category_name' => $service->category->name,
                    'rating' => $service->serviceRating->avg('rating'),
                    'status' => $service->status,
                ];
            }),
            'provider_id' => $this->provider_id,
            'provider_name' => optional($this->provider)->display_name,
            'provider_image' => getSingleMedia($this->provider, 'profile_image', null),
            'providers_service_rating' => optional($this->provider)->getServiceRating->avg('rating'),
            'translations' => ($finalTranslation === '[]' || !$finalTranslation) ? null : $finalTranslation,
            'is_active'=> $this->is_active
        ];
    }
}
