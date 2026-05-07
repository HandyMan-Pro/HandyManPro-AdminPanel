<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyRedeemRule extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_redeem_rules';

    protected $fillable = [
        'loyalty_type',
        'service_id',
        'redeem_type',
        'threshold_points',
        'max_discount',
        'status',
        'is_stackable',
    ];

    protected $casts = [
        'service_id'        => 'integer',
        'threshold_points'  => 'integer',
        'max_discount'      => 'integer',
        'status'            => 'boolean',
        'is_stackable'      => 'boolean',
    ];

    /*
     |--------------------------------------------------------------------------
     | Relations
     |--------------------------------------------------------------------------
     */

    public function serviceMappings()
    {
        return $this->hasMany(LoyaltyRedeemServiceMapping::class, 'loyalty_redeem_id');
    }

    public function partialRules()
    {
        return $this->hasMany(LoyaltyRedeemPartialRule::class, 'redeem_rule_id', 'id');
    }

    public function services()
    {
        return $this->belongsToMany(
            Service::class,
            'loyalty_redeem_service_mappings',
            'loyalty_redeem_id',
            'service_id'
        )->wherePivot('loyalty_type', 'service')
            ->where('status', 1);
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'loyalty_redeem_service_mappings',
            'loyalty_redeem_id',
            'service_id'
        )->wherePivot('loyalty_type', 'category')
            ->where('status', 1);
    }

    public function packages()
    {
        return $this->belongsToMany(
            ServicePackage::class,
            'loyalty_redeem_service_mappings',
            'loyalty_redeem_id',
            'service_id'
        )->wherePivot('loyalty_type', 'package_service')
            ->where('status', 1);
    }

    public static function resolveRedeemRulesByService($service_id)
    {
        $service = Service::with('category')->findOrFail($service_id);

        /* ---------------------------------------------------------
            FETCH SERVICE, CATEGORY & GLOBAL RULES
        --------------------------------------------------------- */

        $rules = LoyaltyRedeemRule::with('partialRules')
            ->where('status', 1)
            ->where(function ($q) use ($service_id, $service) {

                // SERVICE-SPECIFIC RULES
                $q->where(function ($q2) use ($service_id) {
                    $q2->where('loyalty_type', 'service')
                        ->whereHas('services', fn($r) => $r->where('service_id', $service_id));
                });

                // CATEGORY-SPECIFIC RULES
                $q->orWhere(function ($q3) use ($service) {
                    $q3->where('loyalty_type', 'category')
                        ->whereHas('categories', fn($r) => $r->where('service_id', $service->category_id));
                });

                // GLOBAL SERVICE RULES
                $q->orWhere(function ($q4) {
                    $q4->where('loyalty_type', 'service')
                        ->whereDoesntHave('services');
                });

                // GLOBAL CATEGORY RULES
                $q->orWhere(function ($q5) {
                    $q5->where('loyalty_type', 'category')
                        ->whereDoesntHave('categories');
                });
            })
            ->get();

        if ($rules->isEmpty()) {
            return [
                'redeem_type'      => 'full',
                'threshold_points' => 0,
                'max_discount'     => 0,
            ];
        }

        /* ---------------------------------------------------------
            SERVICE VS CATEGORY PRIORITY 
        --------------------------------------------------------- */

        $serviceRules  = $rules->where('loyalty_type', 'service');
        $categoryRules = $rules->where('loyalty_type', 'category');

        if ($serviceRules->isNotEmpty() && $categoryRules->isNotEmpty()) {

            // Calculate max for each rule type (stack vs non-stack)
            $calc = function ($collection) {

                $stackDiscount = $collection->where('is_stackable', 1)->sum('max_discount');

                $bestNonStack = $collection->where('is_stackable', 0)
                    ->sortByDesc('max_discount')
                    ->first();

                $nonStackDiscount = $bestNonStack->max_discount ?? 0;

                $max = max($stackDiscount, $nonStackDiscount);

                $maxType = ($stackDiscount >= $nonStackDiscount) ? 'stack' : 'nonstack';

                return [
                    'max'     => $max,
                    'maxType' => $maxType
                ];
            };

            $serviceData  = $calc($serviceRules);
            $categoryData = $calc($categoryRules);

            // If BOTH select stack → combine both (service + category)
            if ($serviceData['maxType'] === 'stack' && $categoryData['maxType'] === 'stack') {
                $rules = $serviceRules->merge($categoryRules);
            }
            // Otherwise service wins fully
            else {
                $rules = $serviceRules;
            }
        }

        /* ---------------------------------------------------------
            FULL RULE PROCESS
        --------------------------------------------------------- */

        $fullRules = $rules->where('redeem_type', 'full');

        if ($fullRules->isEmpty()) {
            $fullData = [
                'threshold_points' => 0,
                'max_discount'     => 0,
            ];
        } else {

            $stackPoints   = $fullRules->where('is_stackable', 1)->sum('threshold_points');
            $stackDiscount = $fullRules->where('is_stackable', 1)->sum('max_discount');

            $bestNonStack = $fullRules->where('is_stackable', 0)
                ->sortByDesc('max_discount')
                ->first();

            $nsPoints   = $bestNonStack->threshold_points ?? 0;
            $nsDiscount = $bestNonStack->max_discount ?? 0;

            if ($stackDiscount >= $nsDiscount) {
                $fullData = [
                    'threshold_points' => $stackPoints,
                    'max_discount'     => $stackDiscount,
                ];
            } else {
                $fullData = [
                    'threshold_points' => $nsPoints,
                    'max_discount'     => $nsDiscount,
                ];
            }
        }

        /* ---------------------------------------------------------
            PARTIAL PROCESS
        --------------------------------------------------------- */

        $partialRules = $rules->where('redeem_type', 'partial');

        if ($partialRules->isEmpty()) {
            $partialData = [
                'ranges'        => [],
                'max_discount'  => 0,
            ];
        } else {

            $stackable = $partialRules->where('is_stackable', 1);
            $stackRanges = collect();

            foreach ($stackable as $rule) {
                foreach ($rule->partialRules as $pr) {
                    if ($pr->status) {
                        $stackRanges->push([
                            'point_from' => $pr->point_from,
                            'point_to'   => $pr->point_to,
                            'amount'     => $pr->amount,
                        ]);
                    }
                }
            }

            $stackMax = $stackRanges->isNotEmpty()
                ? $stackRanges->max('amount')
                : 0;

            $nonStack = $partialRules->where('is_stackable', 0);
            $bestNonStackMax = 0;
            $bestNonStackRanges = [];

            foreach ($nonStack as $rule) {

                $ruleRanges = collect();

                foreach ($rule->partialRules as $pr) {
                    if ($pr->status) {
                        $ruleRanges->push([
                            'point_from' => $pr->point_from,
                            'point_to'   => $pr->point_to,
                            'amount'     => $pr->amount,
                        ]);
                    }
                }

                if ($ruleRanges->isNotEmpty()) {
                    $maxThis = $ruleRanges->max('amount');

                    if ($maxThis > $bestNonStackMax) {
                        $bestNonStackMax = $maxThis;
                        $bestNonStackRanges = $ruleRanges->values();
                    }
                }
            }

            if ($stackMax >= $bestNonStackMax) {
                $partialData = [
                    'ranges'        => $stackRanges->values(),
                    'max_discount'  => $stackMax,
                ];
            } else {
                $partialData = [
                    'ranges'        => $bestNonStackRanges,
                    'max_discount'  => $bestNonStackMax,
                ];
            }
        }

        /* ---------------------------------------------------------
            FINAL COMPARISON: FULL VS PARTIAL
        --------------------------------------------------------- */

        $fullDiscount    = $fullData['max_discount'];
        $partialDiscount = $partialData['max_discount'];

        if ($fullDiscount >= $partialDiscount) {
            return [
                'redeem_type'      => 'full',
                'threshold_points' => $fullData['threshold_points'],
                'max_discount'     => $fullData['max_discount'],
            ];
        }

        return [
            'redeem_type'      => 'partial',
            'ranges'           => $partialData['ranges'],
            'max_discount'     => $partialData['max_discount'],
        ];
    }

    public static function resolveRedeemRulesByPackage($package_service_id)
    {
        /* ---------------------------------------------------------
            FETCH SERVICE PACKAGES & GLOBAL RULES
        --------------------------------------------------------- */

        // SERVICE PACKAGES-SPECIFIC RULES
        $packageRules = LoyaltyRedeemRule::with('partialRules')
            ->where('loyalty_type', 'package_service')
            ->whereHas('packages', fn($q) => $q->where('service_id', $package_service_id))
            ->where('status', 1)
            ->get();

        // GLOBAL SERVICE PACKAGES RULES
        $globalPackageRules = LoyaltyRedeemRule::with('partialRules')
            ->where('loyalty_type', 'package_service')
            ->whereDoesntHave('packages')
            ->where('status', 1)
            ->get();

        /* ---------------------------------------------------------
            MERAGE RULES
        --------------------------------------------------------- */
        $rules = $packageRules->merge($globalPackageRules);

        if ($rules->isEmpty()) {
            return [
                'redeem_type'      => 'full',
                'threshold_points' => 0,
                'max_discount'     => 0,
            ];
        }

        /* ---------------------------------------------------------
            FULL RULE PROCESS
        --------------------------------------------------------- */
        $fullRules = $rules->where('redeem_type', 'full');

        if ($fullRules->isEmpty()) {
            $fullData = [
                'threshold_points' => 0,
                'max_discount'     => 0,
            ];
        } else {

            // STACKABLE FULL RULES
            $stackPoints   = $fullRules->where('is_stackable', 1)->sum('threshold_points');
            $stackDiscount = $fullRules->where('is_stackable', 1)->sum('max_discount');

            // BEST NON-STACKABLE FULL RULE
            $bestNonStack = $fullRules->where('is_stackable', 0)
                ->sortByDesc('max_discount')
                ->first();

            $nsPoints   = $bestNonStack->threshold_points ?? 0;
            $nsDiscount = $bestNonStack->max_discount ?? 0;

            // FULL DECISION
            if ($stackDiscount >= $nsDiscount) {
                $fullData = [
                    'threshold_points' => $stackPoints,
                    'max_discount'     => $stackDiscount,
                ];
            } else {
                $fullData = [
                    'threshold_points' => $nsPoints,
                    'max_discount'     => $nsDiscount,
                ];
            }
        }

        /* ---------------------------------------------------------
            PARTIAL RULE PROCESS
        --------------------------------------------------------- */
        $partialRules = $rules->where('redeem_type', 'partial');

        if ($partialRules->isEmpty()) {
            $partialData = [
                'ranges'     => collect(),
                'max_discount' => 0,
            ];
        } else {

            /* ---------------------------
                STACKABLE PARTIAL RULES
            --------------------------- */
            $stackRanges = collect();
            foreach ($partialRules->where('is_stackable', 1) as $rule) {
                foreach ($rule->partialRules as $pr) {
                    if ($pr->status) {
                        $stackRanges->push([
                            'point_from' => $pr->point_from,
                            'point_to'   => $pr->point_to,
                            'amount'     => $pr->amount,
                        ]);
                    }
                }
            }

            $stackMax = $stackRanges->isNotEmpty() ? $stackRanges->max('amount') : 0;

            /* ---------------------------
               NON-STACKABLE PARTIAL RULES
            --------------------------- */
            $bestNonMax = 0;
            $bestNonRanges = collect();

            foreach ($partialRules->where('is_stackable', 0) as $rule) {
                $ranges = collect();
                foreach ($rule->partialRules as $pr) {
                    if ($pr->status) {
                        $ranges->push([
                            'point_from' => $pr->point_from,
                            'point_to'   => $pr->point_to,
                            'amount'     => $pr->amount,
                        ]);
                    }
                }

                if ($ranges->isNotEmpty()) {
                    $maxForThis = $ranges->max('amount');

                    if ($maxForThis > $bestNonMax) {
                        $bestNonMax    = $maxForThis;
                        $bestNonRanges = $ranges->values();
                    }
                }
            }

            /* ---------------------------
                PARTIAL DECISION
            --------------------------- */
            if ($stackMax >= $bestNonMax) {
                $partialData = [
                    'ranges'     => $stackRanges->values(),
                    'max_discount' => $stackMax,
                ];
            } else {
                $partialData = [
                    'ranges'     => $bestNonRanges,
                    'max_discount' => $bestNonMax,
                ];
            }
        }

        /* ---------------------------------------------------------
            FINAL DECISION (FULL VS PARTIAL)
        --------------------------------------------------------- */
        $fullPoints    = $fullData['threshold_points'];
        $partialPoints = $partialData['max_discount'];

        if ($fullPoints >= $partialPoints) {
            return [
                'redeem_type'      => 'full',
                'threshold_points' => $fullData['threshold_points'],
                'max_discount'     => $fullData['max_discount'],
            ];
        }

        return [
            'redeem_type' => 'partial',
            'ranges'      => $partialData['ranges'],
            'max_discount'  => $partialData['max_discount'],
        ];
    }
}
