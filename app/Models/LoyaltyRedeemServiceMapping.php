<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyRedeemServiceMapping extends Model
{
    protected $table = 'loyalty_redeem_service_mappings';

    protected $fillable = [
        'loyalty_redeem_id',
        'service_id',
        'loyalty_type',
    ];

    /**
     * Get the earn rule that owns this mapping.
     */
    public function earnRule()
    {
        return $this->belongsTo(LoyaltyRedeemRule::class, 'loyalty_redeem_id');
    }

    /**
     * Get the service related to this mapping (if you have a Service model).
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
