<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\TranslationTrait;

class Category extends BaseModel implements HasMedia
{
    use HasFactory,HasRoles,InteractsWithMedia,SoftDeletes;
    use TranslationTrait;
    protected $table = 'categories';
    protected $fillable = [
        'name', 'description', 'is_featured', 'status' , 'color', 'meta_title', 'meta_description', 'meta_keywords', 'canonical_url', 'seo_enabled', 'slug'
    ];
    protected $casts = [
        'status'    => 'integer',
        'is_featured'  => 'integer',
        'meta_keywords' => 'array',
        'seo_enabled' => 'boolean',
    ];
    public function translations()
    {
        return $this->morphMany(Translations::class, 'translatable');
    }

  public function translate($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';

        // English is always stored in the main column
        if ($locale === 'en') {
            return $this->$attribute ?? '';
        }

        // All other languages live in the translations table
        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->value('value');

        if ($translation !== null && $translation !== '') {
            return $translation;
        }

        // Fallback: return empty string so edit forms show blank for untranslated locales
        return '';
    }

    /**
     * Get translated attribute with fallback for display purposes.
     * Main column always holds English. Falls back: requested → English (main column).
     */
    public function getTranslatedAttribute($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';

        // English is always in the main column
        if ($locale === 'en') {
            return $this->$attribute;
        }

        // Try the requested locale from translations table
        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->value('value');

        if ($translation !== null && $translation !== '') {
            return $translation;
        }

        // Final fallback: English (main column)
        return $this->$attribute;
    }


    public function services(){
        return $this->hasMany(Service::class, 'category_id','id');
    }

    public function subcategories(){
        return $this->hasMany(SubCategory::class, 'category_id', 'id');
    }
    public function scopeList($query)
    {
        return $query->orderByRaw('deleted_at IS NULL DESC, deleted_at DESC')->orderBy('updated_at', 'desc');
    }

    public function serviceZones()
    {
        return $this->belongsToMany(ServiceZone::class, 'category_service_zone');
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('category_image')
            ->singleFile();

        $this->addMediaCollection('seo_image')
            ->singleFile();
    }


    public function index_data()
{
    return $this->hasMany(Media::class, 'module_type', 'id') // Assuming 'module_type' and 'id' are the foreign keys
        ->where('module_type', 'App\Models\Category');
}

    public function loyaltyEarnRulesByCategory()
    {
        $today = now()->toDateString();

        return $this->belongsToMany(
            LoyaltyEarnRule::class,
            'loyalty_earn_service_mappings',
            'service_id',
            'loyalty_earn_id'
        )
        ->wherePivot('loyalty_type', 'category')
        ->where('service_id', $this->category_id)
        ->where('status', 1)
        ->whereDate('start_date', '<=', $today)
        ->whereDate('end_date', '>=', $today);
    }
}
