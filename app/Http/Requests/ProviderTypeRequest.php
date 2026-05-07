<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProviderTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = request()->id;
        return [
            'name'       => 'required|unique:provider_types,name,' . $id,
            'commission' => 'required',
            'status'     => 'required',
        ];
    }

    /**
     * If the English `name` field is empty, pull the first non-empty translation
     * so the `required` rule passes when any language has a name filled.
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['name'] as $field) {
            $current = $this->input($field);
            if ($this->isFilledValue($current)) {
                continue;
            }
            $fromTranslations = $this->getFirstTranslatedValue($field);
            if ($this->isFilledValue($fromTranslations)) {
                $payload[$field] = $fromTranslations;
            }
        }

        if (!empty($payload)) {
            $this->merge($payload);
        }
    }

    private function getFirstTranslatedValue(string $field): ?string
    {
        $translations = $this->input('translations', []);
        if (is_string($translations)) {
            $decoded = json_decode($translations, true);
            $translations = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($translations)) {
            return null;
        }
        foreach ($translations as $translation) {
            if (is_array($translation) && array_key_exists($field, $translation) && $this->isFilledValue($translation[$field])) {
                return (string) $translation[$field];
            }
        }
        return null;
    }

    private function isFilledValue($value): bool
    {
        return is_string($value) ? trim($value) !== '' : !is_null($value) && $value !== '';
    }

    public function messages()
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        if (request()->is('api*')) {
            throw new HttpResponseException(response()->json([
                'status'      => 'false',
                'message'     => $validator->errors()->first(),
                'all_message' => $validator->errors(),
            ], 422));
        }

        throw new HttpResponseException(
            redirect()->back()->withInput()->with('errors', $validator->errors())
        );
    }
}
