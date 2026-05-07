<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyEarnRule extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_earn_rules';

    protected $fillable = [
        'loyalty_type',
        'service_id',
        'minimum_amount',
        'maximum_amount',
        'start_date',
        'end_date',
        'points',
        'status',
        'is_stackable',
    ];

    protected $casts = [
        'service_id' => 'integer',
        'minimum_amount' => 'integer',
        'maximum_amount' => 'integer',
        'status' => 'boolean',
        'is_stackable' => 'boolean',
        'points' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function serviceMappings()
    {
        return $this->hasMany(LoyaltyEarnServiceMapping::class, 'loyalty_earn_id');
    }

    public function services()
    {
        return $this->belongsToMany(
            Service::class,
            'loyalty_earn_service_mappings',
            'loyalty_earn_id',
            'service_id'
        )->wherePivot('loyalty_type', 'service');
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'loyalty_earn_service_mappings',
            'loyalty_earn_id',
            'service_id'
        )->wherePivot('loyalty_type', 'category');
    }

    public function packages()
    {
        return $this->belongsToMany(
            ServicePackage::class,
            'loyalty_earn_service_mappings',
            'loyalty_earn_id',
            'service_id'
        )->wherePivot('loyalty_type', 'package_service');
    }


    public function scopeActive($query)
    {
        return $query->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now());
            });
    }


    public static function userEarnPointsByService($serviceId, $subTotal)
    {
        $service = Service::with('category')->findOrFail($serviceId);

        /* ---------------------------------------------------------
            1. FETCH ALL RULE TYPES
        --------------------------------------------------------- */

        $serviceRule = LoyaltyEarnRule::where('loyalty_type', 'service')
            ->whereHas('services', fn($q) => $q->where('service_id', $serviceId))
            ->active()
            ->get();

        $globalServiceRule = LoyaltyEarnRule::where('loyalty_type', 'service')
            ->whereDoesntHave('services')
            ->active()
            ->get();

        $categoryRule = LoyaltyEarnRule::where('loyalty_type', 'category')
            ->whereHas('categories', fn($q) => $q->where('service_id', $service->category_id))
            ->active()
            ->get();

        $globalCategoryRule = LoyaltyEarnRule::where('loyalty_type', 'category')
            ->whereDoesntHave('categories')
            ->active()
            ->get();

        /* ---------------------------------------------------------
            2. MERGE ALL RULES
        --------------------------------------------------------- */

        $rules = $serviceRule
            ->merge($globalServiceRule)
            ->merge($categoryRule)
            ->merge($globalCategoryRule);

        if ($rules->isEmpty()) {
            return 0;
        }

        /* ---------------------------------------------------------
            3. FILTER ELIGIBLE RULES BY SUBTOTAL
        --------------------------------------------------------- */

        $eligible = $rules->filter(
            fn($rule) =>
            $subTotal >= $rule->minimum_amount &&
            $subTotal <= $rule->maximum_amount
        );

        if ($eligible->isEmpty()) {
            return 0;
        }

        /* ---------------------------------------------------------
            4. SPLIT TYPES
        --------------------------------------------------------- */

        $serviceEligible  = $eligible->where('loyalty_type', 'service');
        $categoryEligible = $eligible->where('loyalty_type', 'category');

        /* ---------------------------------------------------------
            5. STACKABLE + NONSTACKABLE CALCULATION
        --------------------------------------------------------- */

        $calc = function ($collection) {
            $stack = $collection->where('is_stackable', 1)->sum('points');
            $nonstack = $collection
                ->where('is_stackable', 0)
                ->sortByDesc('points')
                ->first()
                ?->points ?? 0;

            // Detect rule type (stack or nonstack) used for max
            $maxType = ($stack >= $nonstack) ? 'stack' : 'nonstack';
            $max = max($stack, $nonstack);

            return [
                'stack'    => $stack,
                'nonstack' => $nonstack,
                'max'      => $max,
                'maxType'  => $maxType,
            ];
        };

        /* ---------------------------------------------------------
            6. BOTH SERVICE + CATEGORY EXIST → APPLY COMBINED FLOW
        --------------------------------------------------------- */

        if ($serviceEligible->isNotEmpty() && $categoryEligible->isNotEmpty()) {

            $serviceData  = $calc($serviceEligible);
            $categoryData = $calc($categoryEligible);

            // If BOTH selected max rules are from stack → sum
            if ($serviceData['maxType'] === 'stack' && $categoryData['maxType'] === 'stack') {
                return $serviceData['max'] + $categoryData['max'];
            }

            // Otherwise → SERVICE WINS
            return $serviceData['max'];
        }

        /* ---------------------------------------------------------
            7. ONLY SERVICE RULES
        --------------------------------------------------------- */

        if ($serviceEligible->isNotEmpty()) {
            $data = $calc($serviceEligible);
            return $data['max'];
        }

        /* ---------------------------------------------------------
            8. ONLY CATEGORY RULES
        --------------------------------------------------------- */

        if ($categoryEligible->isNotEmpty()) {
            $data = $calc($categoryEligible);
            return $data['max'];
        }

        return 0;
    }


    public static function userEarnPointsByPackage($packageId, $subTotal)
    {
        $package = ServicePackage::findOrFail($packageId);

         /* ---------------------------------------------------------
            FETCH SERVICE PACKAGES & GLOBAL RULES
        --------------------------------------------------------- */

        // SERVICE PACKAGES-SPECIFIC RULES
        $packageRules = LoyaltyEarnRule::where('loyalty_type', 'package_service')
            ->whereHas('packages', fn($q) => $q->where('service_id', $package->id))
            ->active()
            ->get();

        // GLOBAL SERVICE PACKAGES RULES
        $globalPackageRules = LoyaltyEarnRule::where('loyalty_type', 'package_service')
            ->whereDoesntHave('packages')
            ->active()
            ->get();

        /* ---------------------------------------------------------
            MERAGE RULES
        --------------------------------------------------------- */
        $rules = $packageRules->merge($globalPackageRules);

        if ($rules->isEmpty()) {
            return 0;
        }

        /* ---------------------------------------------------------
            3. FILTER ELIGIBLE RULES BY SUBTOTAL RANGE
        --------------------------------------------------------- */
        $eligible = $rules->filter(
            fn($rule) => $subTotal >= $rule->minimum_amount && $subTotal <= $rule->maximum_amount
        );

        if ($eligible->isEmpty()) {
            return 0;
        }

        // Stackable points sum
        $stackablePoints = $eligible
            ->where('is_stackable', 1)
            ->sum('points');

        // Best non-stackable rule (by priority → points)
        $nonStackablePoints = $eligible
            ->where('is_stackable', 0)
            ->sortByDesc('priority')
            ->sortByDesc('points')
            ->first()
            ?->points ?? 0;

        // get max points between stackable and non-stackable
        $maxPoints = max($stackablePoints, $nonStackablePoints);

        return $maxPoints;
    }
}
