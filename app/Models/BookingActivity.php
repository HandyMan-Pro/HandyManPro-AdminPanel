<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BookingActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'booking_activities';

    protected $fillable = [
        'datetime', 'booking_id', 'activity_type', 'activity_message', 'activity_data', 'activity_slug'
    ];

    protected $casts = [
        'booking_id'   => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Only generate slug if it's not already set
            if (empty($model->activity_slug) && !empty($model->activity_type)) {
                $model->activity_slug = static::generateSlug($model->activity_type);
            }
        });

        static::updating(function ($model) {
            // Only generate slug if it's not already set
            if (empty($model->activity_slug) && !empty($model->activity_type)) {
                $model->activity_slug = static::generateSlug($model->activity_type);
            }
        });
    }

    /**
     * Generate a URL-friendly slug from the activity type
     *
     * @param string $activityType
     * @return string
     */
    public static function generateSlug($activityType)
    {
        // Preserve underscores and convert spaces to underscores
        $slug = str_replace(' ', '_', $activityType);
        // Use underscore as separator instead of hyphen, but don't convert existing underscores
        return Str::slug($slug, '_');
    }

    /**
     * Get the activity slug, generating it if necessary
     *
     * @return string
     */
    public function getActivitySlugAttribute($value)
    {
        // For existing records, we'll convert hyphens to underscores to maintain consistency
        if (!empty($value)) {
            return str_replace('-', '_', $value);
        }

        if (!empty($this->activity_type)) {
            return static::generateSlug($this->activity_type);
        }

        return '';
    }
}
