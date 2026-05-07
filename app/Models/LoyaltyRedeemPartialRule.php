<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyRedeemPartialRule extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_redeem_partial_rules';

    protected $fillable = [
        'redeem_rule_id',
        'rule_name',
        'point_from',
        'point_to',
        'amount',
        'status'
    ];

    protected $casts = [
        'point_from' => 'integer',
        'point_to' => 'integer',
        'amount' => 'integer',
        'status' => 'boolean',
    ];

    public function loyaltyRedeemRule()
    {
        return $this->belongsTo(LoyaltyRedeemRule::class, 'redeem_rule_id');
    }
}
