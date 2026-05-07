<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\TranslationTrait;

class Shop extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, TranslationTrait;

    protected $table = 'shops';

    protected $fillable = [
        'provider_id',
        'shop_name',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'lat',
        'long',
        'registration_number',
        'shop_start_time',
        'shop_end_time',
        'contact_number',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lat' => 'float',
        'long' => 'float',
        'shop_start_time' => 'datetime',
        'shop_end_time' => 'datetime',
    ];

    public function translations()
    {
        return $this->morphMany(Translations::class, 'translatable');
    }

    public function translate($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';
        $defaultLanguage = getDefaultLanguage();
        
        // English is always in the main column
        if ($locale === 'en') {
            return $this->$attribute ?? '';
        }

        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->value('value');

        return ($translation !== null && $translation !== '') ? $translation : ($this->$attribute ?? '');
    }

    /**
     * Get translated attribute with fallback for display purposes.
     */
    public function getTranslatedAttribute($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';

        if ($locale === 'en') {
            return $this->$attribute;
        }

        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->value('value');

        return ($translation !== null && $translation !== '') ? $translation : $this->$attribute;
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }


    public function services()
    {
        return $this->belongsToMany(Service::class, 'shop_service_mappings', 'shop_id', 'service_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('shop_attachment');
    }
    public function scopeNearByShop($query, $lat, $long)
    {
        if (!empty($lat) && !empty($long)) {
            try {
                $get_distance = getSettingKeyValue('site-setup', 'radious');
                $get_unit = getSettingKeyValue('site-setup', 'distance_type');
                $unit_value = $get_unit === 'km' ? 6371 : 3959;

                $query->selectRaw(
                    "shops.*, ({$unit_value} * acos(
                    cos(radians(?)) * cos(radians(lat)) *
                    cos(radians(`long`) - radians(?)) +
                    sin(radians(?)) * sin(radians(lat))
                )) AS distance",
                    [$lat, $long, $lat]
                )
                    ->having('distance', '<=', $get_distance)
                    ->orderBy('distance', 'asc');
            } catch (\Exception $e) {
                \Log::error('Nearby shop filter error: ' . $e->getMessage());
            }
        }
        return $query;
    }

    public function shopDocument()
    {
        return $this->hasMany(ShopDocument::class, 'shop_id', 'id');
    }

    public function shopHours()
    {
        return $this->hasMany(ShopHour::class, 'shop_id', 'id');
    }
}
