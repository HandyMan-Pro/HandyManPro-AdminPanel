<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPointActivity extends Model
{
    protected $table = 'loyalty_point_activities';

    protected $fillable = [
        'user_id',
        'type',
        'points',
        'source',
        'earn_type',
        'related_id',
        'description',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'points' => 'integer',
        'related_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
