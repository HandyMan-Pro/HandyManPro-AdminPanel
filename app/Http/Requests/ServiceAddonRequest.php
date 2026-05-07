<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ServiceAddonRequest extends FormRequest
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
        // Only merge a translation into `name` if the English tab (bare `name` field) is empty
        $current = $this->input('name', '');
        if (trim($current) !== '') {
            return; // English name already provided — don't touch it
        }

        $translations = $this->input('translations', []);
        if (!is_array($translations)) {
            return;
        }

        foreach ($translations as $lang => $fields) {
            if (!empty($fields['name'])) {
                $this->merge(['name' => $fields['name']]);
                break;
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
        return [
            //
            'name'                           => 'required|unique:service_addons,name,'.$id,
            'translations.*.name'            => 'nullable', // Allow name in any language
            'service_id'                     => 'required',
            'price'                          => 'required|min:0',
            'serviceaddon_image'             => 'mimes:jpg,jpeg,png,webp'
        ];
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
