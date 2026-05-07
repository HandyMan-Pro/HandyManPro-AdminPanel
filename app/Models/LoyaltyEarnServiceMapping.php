<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class LoyaltyEarnServiceMapping extends Model
{
    protected $table = 'loyalty_earn_service_mappings';

    protected $fillable = [
        'loyalty_earn_id', // loyalty_earn_id
        'service_id', // service_id ro service_package_id or category_id
        'loyalty_type', // service / service_package / service_category
    ];

 public function earnRule()
    {
        return $this->belongsTo(LoyaltyEarnRule::class, 'loyalty_earn_id');
    }

    /**
     * Dynamic relation based on loyalty_type.
     * service        => Service::class
     * service_package => ServicePackage::class
     * service_category => Category::class
     */
    public function item()
    {
        return match ($this->loyalty_type) {
            'service'          => $this->belongsTo(Service::class, 'service_id'),
            'package_service'  => $this->belongsTo(ServicePackage::class, 'service_id'),
            'category' => $this->belongsTo(Category::class, 'service_id'),
            default            => null,
        };
    }

    // Optional: direct helper accessors
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id')
                    ->where('loyalty_type', 'service');
    }

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class, 'service_id')
                    ->where('loyalty_type', 'package_service');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'service_id')
                    ->where('loyalty_type', 'category');
    }
}
