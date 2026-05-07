<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyReferralRule extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_referral_rules';

     protected $fillable = [
        'referrer_points',
        'referred_user_points',
        'max_referrals_per_user',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'referrer_points' => 'integer',
        'referred_user_points' => 'integer',
        'max_referrals_per_user' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => 'boolean',
    ];
}
