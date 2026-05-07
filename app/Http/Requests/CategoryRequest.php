<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;
        
        $rules = [
            'name'              => 'required|max:50|unique:categories,name,'.$id,
            'status'            => 'required',
        ];

        // Only apply SEO validation if SEO is enabled
        if (request()->has('seo_enabled') && request()->seo_enabled) {
            $rules['meta_title'] = 'required|string|max:255|unique:categories,meta_title,'.$id;
            $rules['meta_description'] = 'required|string|max:200';
            $rules['meta_keywords'] = 'required|string';
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];
        foreach (['name', 'meta_title', 'meta_description', 'meta_keywords'] as $field) {
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

}
