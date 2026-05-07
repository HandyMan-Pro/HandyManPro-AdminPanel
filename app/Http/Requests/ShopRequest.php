<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class ShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation: ensure shop_name is set from English or first available translation
     * when the user filled a non-English language tab only.
     */
    protected function prepareForValidation(): void
    {
        // Multipart clients often send `translations` as a JSON string.
        $translations = $this->input('translations');
        if (is_string($translations)) {
            $decoded = json_decode($translations, true);
            $this->merge(['translations' => is_array($decoded) ? $decoded : []]);
        }

        // Normalize lat/long inputs (API can send them as strings, decimals with comma, etc.)
        // Then we round to match DB DECIMAL(10,7) precision before inserting.
        $lat = $this->input('lat');
        if (is_string($lat)) {
            $lat = str_replace(',', '.', trim($lat));
        }
        if ($lat !== null && $lat !== '') {
            $this->merge(['lat' => round((float) $lat, 7)]);
        }

        $long = $this->input('long');
        if (is_string($long)) {
            $long = str_replace(',', '.', trim($long));
        }
        if ($long !== null && $long !== '') {
            $this->merge(['long' => round((float) $long, 7)]);
        }

        $shopName = $this->input('shop_name');
        $primary_locale = app()->getLocale() ?? 'en';

        if (trim((string) $shopName) === '' && is_array($this->input('translations'))) {
            $translations = $this->input('translations');

            // 1) Prefer English translation if provided
            $shopName = $translations['en']['shop_name'] ?? $translations['en']['name'] ?? null;

            // 2) Mirror ServiceController: if primary locale is not English and shop_name missing,
            //    copy translations.primary_locale.name into shop_name for fallback.
            if (trim((string) ($shopName ?? '')) === '' && $primary_locale !== 'en') {
                $shopName = $translations[$primary_locale]['shop_name'] ?? $translations[$primary_locale]['name'] ?? null;
            }

            // 3) Fallback: first non-empty translated shop_name/name
            if (trim((string) ($shopName ?? '')) === '') {
                $shopName = collect($translations)
                    ->map(function ($row) {
                        if (!is_array($row)) {
                            return null;
                        }
                        return $row['shop_name'] ?? $row['name'] ?? null;
                    })
                    ->filter(function ($v) {
                        return trim((string) $v) !== '';
                    })
                    ->first();
            }

            $this->merge(['shop_name' => $shopName ?? '']);
        }
    }

    /**
     * Custom validation messages shown to providers.
     */
    public function messages(): array
    {
        return [
            'lat.between' => 'Latitude must be between -90 and 90',
            'long.between' => 'Longitude must be between -180 and 180',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        $shopId = $this->route('shop') ?? $this->route('id'); // Adjust to match your route

        return [
            'provider_id' => 'required|exists:users,id',
            'shop_name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'required|string',
            // Latitude: [-90..90], Longitude: [-180..180]
            // These constraints prevent SQLSTATE[22003] numeric out of range for shops.lat/long.
            'lat' => 'nullable|numeric|between:-90,90',
            'long' => 'nullable|numeric|between:-180,180',
            'registration_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shops', 'registration_number')->ignore($shopId),
            ],
            // 'shop_start_time' => 'required|date_format:H:i',
            // 'shop_end_time' => 'required|date_format:H:i|after:shop_start_time',
            'contact_number' => 'required|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('shops', 'email')->ignore($shopId),
            ],
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'shop_attachment.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
