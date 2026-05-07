<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\TranslationTrait;

class FrontendSetting extends Model implements  HasMedia
{
    use InteractsWithMedia,HasFactory,TranslationTrait;

    protected $table = 'frontend_settings';
    protected $fillable = [
        'type','key','status','value'
    ];

    protected $casts = [
        'status'     => 'integer',
    ];
    
    public static function getValueByKey($key)
    {
        $setting = self::where('key', $key)->first();

        return $setting ? json_decode($setting->value) : null;
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
