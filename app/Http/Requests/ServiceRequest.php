<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function attributes(): array
    {
        // Make validation errors display a friendly translated label instead of
        // `translations.<locale>.name`.
        $primaryLocale = app()->getLocale() ?? 'en';
        $serviceLabel = __('messages.service') ?? 'Service';
        $nameLabel = __('messages.name') ?? 'Name';

        return [
            'translations.' . $primaryLocale . '.name' => trim($serviceLabel . ' ' . $nameLabel),
        ];
    }

    protected function prepareForValidation(): void
    {
        $translations = $this->input('translations');

        // Some update forms submit translations as JSON string or empty string.
        // Normalize it so "translations must be array" does not fail incorrectly.
        if (is_string($translations)) {
            $decoded = json_decode($translations, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['translations' => $decoded]);
            } elseif (trim($translations) === '') {
                $this->merge(['translations' => []]);
            }
        }

        $primaryLocale = app()->getLocale() ?? 'en';
        $translations = $this->input('translations');
        $baseName = trim((string) ($this->input('name') ?? ''));
        
        // If base name is empty, try to find a name from ANY language
        if ($baseName === '' && is_array($translations)) {
            // First try primary locale
            if (isset($translations[$primaryLocale]['name']) && trim($translations[$primaryLocale]['name']) !== '') {
                $this->merge(['name' => $translations[$primaryLocale]['name']]);
            } else {
                // Try any other language
                foreach ($translations as $locale => $localeData) {
                    if (!is_array($localeData)) {
                        continue;
                    }
                    $translatedName = trim((string) ($localeData['name'] ?? ''));
                    if ($translatedName !== '') {
                        $this->merge(['name' => $translatedName]);
                        break;
                    }
                }
            }
        }
        
        // If primary locale translation is empty but we have a base name or another language,
        // populate the primary locale translation
        if ($primaryLocale !== 'en' && is_array($translations)) {
            $primaryName = $translations[$primaryLocale]['name'] ?? null;
            
            if (trim((string)($primaryName ?? '')) === '') {
                $baseName = trim((string) ($this->input('name') ?? ''));
                
                if ($baseName !== '') {
                    // Use base name
                    $translations[$primaryLocale]['name'] = $baseName;
                    $this->merge(['translations' => $translations]);
                } else {
                    // Try to find name from any other language
                    foreach ($translations as $locale => $localeData) {
                        if (!is_array($localeData) || $locale === $primaryLocale) {
                            continue;
                        }
                        $translatedName = trim((string) ($localeData['name'] ?? ''));
                        if ($translatedName !== '') {
                            $translations[$primaryLocale]['name'] = $translatedName;
                            $this->merge(['translations' => $translations]);
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;
        $providerId = request()->provider_id ?? auth()->user()->id; // Get provider_id from request or auth user
        $isApi = request()->is('api/*');
        $primaryLocale = app()->getLocale() ?? 'en';

        $nameRules = [
            // If primary language is English, validate services.name.
            // Otherwise the form sends the name under translations[{locale}][name].
            $primaryLocale === 'en' ? 'required' : 'nullable',
        ];

        if ($primaryLocale === 'en') {
            $nameRules[] = Rule::unique('services', 'name')
                ->ignore($id) // ignore current service when updating
                ->where(function ($query) use ($providerId) {
                    return $query->where('provider_id', $providerId);
                });
        }

        $rules = [
            // When default language is not English, the form sends the name under
            // translations[{locale}][name] and may leave services.name empty.
            // So we validate the "primary locale" field instead of always requiring "name".
            'name' => $nameRules,
            'translations' => 'array',
            // Don't require specific language - we'll validate manually in prepareForValidation
            'translations.*.name' => 'nullable|string|max:255',
            'category_id'                    => 'required',
            'type'                           => 'required',
            'price'                          => [
                'nullable', // Allow nullable for user-created services from job requests
                'min:0',
                function ($attribute, $value, $fail) use ($providerId) {
                    // Skip validation if price is null or empty
                    if ($value === null || $value === '') {
                        return;
                    }
                    
                    $priceType = request()->input('type');
                    $isPostJob = request()->input('post_job'); // Check if this is from a job request (1 = yes, 0 or null = no)
                    
                    // If post_job=1, allow price = 0 (user creating service from job request)
                    // If post_job is NOT set or = 0, validate price must be > 0 for hourly/fixed types
                    if (!$isPostJob && in_array($priceType, ['hourly', 'fixed']) && $value <= 0) {
                        $fail(__('messages.price_must_be_greater_than_zero_for_hourly_fixed'));
                    }
                    
                    // Only validate commission for non-free price types and when price > 0
                    if ($priceType !== 'free' && $value > 0) {
                        // Get provider details
                        $provider = \App\Models\User::find($providerId);
                        
                        if ($provider && $provider->providertype_id) {
                            $providerType = \App\Models\ProviderType::find($provider->providertype_id);
                            
                            // If provider type is "fixed", check if price is greater than commission
                            if ($providerType && $providerType->type === 'fixed') {
                                $commission = $providerType->commission ?? 0;
                                
                                if ($value <= $commission) {
                                    $fail(__('messages.service_price_must_be_greater_than_commission', [
                                        'commission' => getPriceFormat($commission)
                                    ]));
                                }
                            }
                        }
                    }
                }
            ],
            'status'                         => 'required',
            'service_attachment.*'           => 'image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max per file
        ];

        // Require at least one attachment for new services
        if (!$id) {
            if ($isApi){
                $rules['attachment_count'] = 'required|integer|min:1';
                $rules['service_attachment_0'] = 'required|file|image|mimes:jpeg,png,jpg,gif|max:10240';
                $rules['service_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['service_attachment'] = 'required|array|min:1';
                $rules['service_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        } else {
            if ($isApi) {
                $rules['service_attachment_*'] = 'file|image|mimes:jpeg,png,jpg,gif|max:10240';
            } else {
                $rules['service_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        }
        // Only apply SEO validation if SEO is enabled
        if (request()->has('seo_enabled') && request()->seo_enabled) {
            $rules['meta_title'] = 'required|string|max:255|unique:services,meta_title,'.$id;
            $rules['meta_description'] = 'required|string|max:200';
            $rules['meta_keywords'] = 'required|string';
        }

        return $rules;
    }
    public function messages()
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        if ( request()->is('api*')){
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data,422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
