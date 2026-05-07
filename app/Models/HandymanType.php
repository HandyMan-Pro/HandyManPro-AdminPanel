<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Traits\TranslationTrait;

class HandymanType extends Model
{
    use HasFactory,SoftDeletes;
    use TranslationTrait;
    protected $table = 'handyman_types';
    protected $fillable = [
        'name', 'commission', 'status','type','created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'commission'=> 'double',
        'status'    => 'integer',
    ];
    public function translations()
    {
        return $this->morphMany(Translations::class, 'translatable');
    }

   public function translate($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';
        
        // Try to get translation for the requested locale
        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->value('value');

        // If translation exists, return it
        if ($translation !== null && $translation !== '') {
            return $translation;
        }
        
        // No translation found - return empty string
        // This ensures that when default language changes, only actual translations are shown
        return '';
    }

    /**
     * Get translated attribute with fallback for display purposes.
     * Uses already eager-loaded translations to avoid N+1 queries.
     * Returns empty string if no translation exists for the requested locale (except English which uses main column).
     */
    public function getTranslatedAttribute($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';

        // English always lives in the main column
        if ($locale === 'en') {
            return $this->$attribute;
        }

        // Use eager-loaded translations (no extra query)
        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        $translation = $translations
            ->where('locale', $locale)
            ->where('attribute', $attribute)
            ->first();

        // Return translation value if exists, otherwise empty (not English fallback)
        return ($translation && $translation->value !== '') ? $translation->value : '';
    }
    public function scopeList($query)
    {
        return $query->orderByRaw('deleted_at IS NULL DESC, deleted_at DESC')->orderBy('created_at', 'desc');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by','id');
    }
    public static function boot()
    {
        parent::boot();

        // Set created_by when creating a new handyman type
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->user()->id;  // Assuming 'auth' is the admin
                $model->updated_by = auth()->user()->id;
            }
        });

        // Set updated_by when updating an existing handyman type
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->user()->id;
            }
        });
    }
}
