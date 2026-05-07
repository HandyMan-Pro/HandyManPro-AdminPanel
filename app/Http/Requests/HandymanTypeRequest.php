<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class HandymanTypeRequest extends FormRequest
{
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Only populate bare 'name' from translations if it's truly empty.
        // Prefer English translation; never overwrite an already-filled English name.
        $bareName = $this->input('name', '');
        if (trim((string) $bareName) === '' && $this->has('translations') && is_array($this->translations)) {
            // Try English first
            $enName = $this->translations['en']['name'] ?? '';
            if (trim($enName) !== '') {
                $this->merge(['name' => $enName]);
            } else {
                // Fallback: first non-empty translation
                foreach ($this->translations as $lang => $fields) {
                    if (!empty($fields['name'])) {
                        $this->merge(['name' => $fields['name']]);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;
        $createdBy = Auth::id();
        
        // For API requests, keep the original validation
        if (request()->is('api/*')) {
            return [
                'name' => [
                    'required',
                    Rule::unique('handyman_types')
                        ->where(function ($query) use ($createdBy) {
                            return $query->where('created_by', $createdBy);
                        })
                        ->ignore($id),
                ],
                'commission'        => 'required',
                'status'            => 'required',
            ];
        }
        
        // For web requests, name is not required at the top level
        // We'll validate it in the controller to check translations
        return [
            'commission'        => 'required',
            'status'            => 'required',
        ];
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
