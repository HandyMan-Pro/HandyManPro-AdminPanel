<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Http\Resources\API\CouponResource;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function getCouponList(Request $request){

        $service_id = $request->service_id;
        $price = $request->price; // Get the price parameter

        // Start building the query
        $query = Coupon::where('status', 1)
            ->with('serviceAdded')
            ->whereHas('serviceAdded', function ($query) use ($service_id) {
                $query->where('service_id', $service_id);
            });

        // If price is provided, filter coupons to only include those with discount < price for fixed discounts
        if ($price !== null) {
            $query->where(function ($query) use ($price) {
                // Include percentage discount coupons (they don't have a fixed value)
                $query->where('discount_type', 'percentage')
                    // Include fixed discount coupons that are less than the price (not equal to)
                    ->orWhere(function ($subQuery) use ($price) {
                        $subQuery->where('discount_type', 'fixed')
                            ->where('discount', '<', $price);
                    });
            });
        }

        $coupon = $query->orderBy('created_at','desc')->get();

        $currentDate = Carbon::today();

        $expire_cupon=$coupon->where('expire_date', '<', $currentDate);

        $valid_cupon=$coupon->where('expire_date', '>', $currentDate);

        $response = [
            'expire_cupon' => CouponResource::collection($expire_cupon),
            'valid_cupon' => CouponResource::collection($valid_cupon),
        ];

        return comman_custom_response($response);
    }
}
