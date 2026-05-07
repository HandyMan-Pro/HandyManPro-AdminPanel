<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Documents;

class UserRequest extends FormRequest
{
    private ?bool $nearbyProviderEnabled = null;

    private function isNearbyProviderEnabled(): bool
    {
        if ($this->nearbyProviderEnabled !== null) {
            return $this->nearbyProviderEnabled;
        }

        $otherSetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();

        if (!$otherSetting) {
            $this->nearbyProviderEnabled = false;
            return $this->nearbyProviderEnabled;
        }

        $decoded = json_decode($otherSetting->value, true);
        $this->nearbyProviderEnabled = (bool) ($decoded['nearby_provider'] ?? 0);

        return $this->nearbyProviderEnabled;
    }

    private function shouldShowLocationServiceMessage(): bool
    {
        return $this->input('user_type') === 'provider' && $this->isNearbyProviderEnabled();
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
        \Log::info('Validating user request');
        \Log::info(json_encode(request()->all()));

        $id = request()->id;
        $nearbyProvider = $this->isNearbyProviderEnabled();

        // Check if this is a registration (no id) or update (has id)
        $isRegistration = empty($id);
        
        $rules = [
            'username'       => 'required|max:255|unique:users,username,' . $id,
            'email'          => 'required|email|max:255|unique:users,email,' . $id,
            'contact_number' => 'required|unique:users,contact_number,' . $id,
            'profile_image'  => 'nullable|mimetypes:image/jpeg,image/png,image/jpg,image/gif',
            'address'        => ($this->input('user_type') === 'provider' && $nearbyProvider) ? 'required' : 'nullable',
            // 'country_id'     => ($this->input('user_type') === 'provider' && $nearbyProvider) ? 'required' : 'nullable',
            // 'state_id'       => ($this->input('user_type') === 'provider' && $nearbyProvider) ? 'required' : 'nullable',
            // 'city_id'        => ($this->input('user_type') === 'provider' && $nearbyProvider) ? 'required' : 'nullable',
            'latitude'       => ($this->input('user_type') === 'provider' && $nearbyProvider) ? 'required' : 'nullable',
            'longitude'      => ($this->input('user_type') === 'provider' && $nearbyProvider) ? 'required' : 'nullable',
        ];

        // Applies to user, provider, and handyman signup via API (register + add-user when no id)
        $passwordRule = 'required|string|min:8|max:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,12}$/';
        if ($id) {
            $rules['password'] = 'nullable|string|min:8|max:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,12}$/';
        } else {
            $rules['password'] = $passwordRule;
        }

        // Only validate documents if provider is registering
        if ($this->input('user_type') === 'provider' && request()->is('api/*')) {
            $allDocIds = Documents::pluck('id')->toArray();
            $rules['document_id'] = ['nullable', 'array'];
            $rules['document_id.*'] = ['in:' . implode(',', $allDocIds)];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('user_type') === 'provider' && request()->is('api/*')) {
                $submittedIds = (array) $this->input('document_id', []);
                
                // Check if there are any active documents at all
                $activeDocuments = Documents::where('status', 1)
                    ->where('type', 'provider_document')
                    ->exists();
                
                // Only validate if there are active documents
                if ($activeDocuments) {
                    $requiredDocs = Documents::where('is_required', 1)
                        ->where('status', 1)
                        ->where('type', '!=', 'shop_document')
                        ->pluck('id')
                        ->toArray();

                    // 1. Check missing required document IDs
                    $missingRequired = array_diff($requiredDocs, $submittedIds);
                    if (!empty($missingRequired)) {
                        $docNames = Documents::whereIn('id', $missingRequired)
                            ->where('type', '!=', 'shop_document')
                            ->pluck('name')
                            ->toArray();
                        $validator->errors()->add('document_id', 'Missing required documents: ' . implode(', ', $docNames));
                    }

                    // 2. Check if file for required document ID is uploaded
                    foreach ($submittedIds as $index => $docId) {
                        if (in_array($docId, $requiredDocs)) {
                            $fileKey = "provider_document_$index";
                            if (!$this->hasFile($fileKey)) {
                                $docName = Documents::where('id', $docId)->value('name') ?? "ID $docId";
                                $validator->errors()->add($fileKey, "Missing file for required document: $docName");
                            }
                        }
                    }
                }
            }
        });
    }

    public function messages()
    {
        $messages = [
            'profile_image.*' => __('messages.image_png_gif'),
            'password.regex'  => __('messages.password_must_contain'),
            'password.min'    => __('messages.password_length_8_12'),
            'password.max'    => __('messages.password_length_8_12'),
        ];

        if ($this->shouldShowLocationServiceMessage()) {
            $messages = array_merge($messages, [
                'latitude.required' => 'Location services temporarily unavailable, please contact admin.',
                'longitude.required' => 'Location services temporarily unavailable, please contact admin.',
                'address.required' => 'Location services temporarily unavailable, please contact admin.',
            ]);
        }

        return $messages;
    }
    
    public function attributes()
    {
        return [
            'country_id' => 'location information',
            'state_id' => 'location information',
            'city_id' => 'location information',
            'address' => 'location information',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if (request()->is('api*')) {
            $data = [
                'status' => false,
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data, 406));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
