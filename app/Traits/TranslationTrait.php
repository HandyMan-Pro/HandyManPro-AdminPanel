<?php

namespace App\Traits;

trait TranslationTrait
{

    public function languagesArray()
    {
        $language_option = sitesetupSession('get')->language_option ?? ["ar","nl","en","fr","de","hi","it"];
        $language_array = languagesArray($language_option);
        return $language_array;
    }
    /**
     * Generate an array of languages with name and flag path.
     *
     * @return array
     */
    public function getLanguageArray()
    {
        // Get language options from the session or set default languages
        $language_option = sitesetupSession('get')->language_option ?? ["ar","nl","en","fr","de","hi","it"];
        // Generate language array with title and flag path
        $language_array = [];
        foreach ($language_option as $lang_id) {
            $language_array[] = [
                'id' => $lang_id,
                'title' => strtoupper($lang_id),
                'flag_path' => file_exists(public_path('/images/flags/' . $lang_id . '.png'))
                    ? asset('/images/flags/' . $lang_id . '.png')
                    : asset('/images/language.png')
            ];
        }

        return $language_array;
    }

    public function saveTranslations(array $data, array $attributes, array $language_option, $primary_locale)
    {
        // English always lives in the main column — never store an 'en' translation row.
        // All other locales are stored in the translations table.
        // The form submits non-English languages as translations[locale][attr].

        $translationsMap = is_array($data['translations'] ?? null) ? $data['translations'] : [];

        foreach ($translationsMap as $locale => $fields) {
            if ($locale === 'en') {
                // English lives in the main column — skip
                continue;
            }

            if (!is_array($fields)) {
                continue;
            }

            foreach ($attributes as $attribute) {
                if (!array_key_exists($attribute, $fields)) {
                    // Not submitted for this locale — keep existing
                    continue;
                }

                $value = $fields[$attribute];

                if ($value !== null && trim((string) $value) !== '') {
                    $this->translations()->updateOrCreate(
                        ['locale' => $locale, 'attribute' => $attribute],
                        ['value' => $value]
                    );
                } else {
                    // Empty submitted — remove translation row
                    $this->translations()
                        ->where('locale', $locale)
                        ->where('attribute', $attribute)
                        ->delete();
                }
            }
        }
    }

    function getTranslation($translations, $locale, $attribute = 'name', $fallbackValue = null)
    {
        if (!$translations || !is_iterable($translations)) {
            return $fallbackValue;
        }

        $collection = collect($translations);

        // English always lives in the main column — return it directly.
        if ($locale === 'en') {
            return $fallbackValue;
        }

        // Find the translation for the requested locale and attribute
        $translation = $collection->where('locale', $locale)->where('attribute', $attribute)->first();

        if ($translation && $translation->value !== '') {
            return $translation->value;
        }

        // No translation found for this locale — fall back to English (main column).
        return $fallbackValue;
    }
}
