<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\TranslationTrait;

class SeoSetting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use TranslationTrait;

    protected $fillable = [
        'meta_title',
        'meta_description',
        'meta_keywords',
        'site_meta_description',
        'global_canonical_url',
        'google_site_verification',
        'seo_image',
    ];

    protected $casts = [
        'meta_keywords' => 'array', // Cast JSON to array
    ];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('seo_image')
            ->singleFile();
    }

    /**
     * Get meta keywords as array
     */
    public function getMetaKeywordsArrayAttribute()
    {
        return is_array($this->meta_keywords) ? $this->meta_keywords : [];
    }

    /**
     * Set meta keywords from array
     */
    public function setMetaKeywordsArrayAttribute($value)
    {
        $this->meta_keywords = is_array($value) ? $value : [];
    }

    public function translations()
    {
        return $this->morphMany(Translations::class, 'translatable');
    }

    public function translate($attribute, $locale = null)
    {
        
        $locale = $locale ?? app()->getLocale() ?? 'en';
        if($locale !== 'en'){
            $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->value('value');

        return $translation !== null ?  $translation : '';
        }
        return $this->$attribute;
    }
}
