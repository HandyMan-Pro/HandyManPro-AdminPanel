<?php

namespace App\Http\Controllers\API;

use App\Models\Tax;
use App\Models\Shop;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\Category;
use App\Traits\ZoneTrait;
use App\Models\ServiceFaq;
use App\Models\ServiceZone;
use App\Models\ServiceAddon;
use Illuminate\Http\Request;
use App\Models\BookingRating;
use App\Models\LoyaltyEarnRule;
use App\Models\LoyaltyRedeemRule;
use App\Models\ProviderTaxMapping;
use App\Models\ServiceZoneMapping;
use App\Http\Controllers\Controller;
use App\Models\CouponServiceMapping;
use App\Models\UserFavouriteService;
use App\Http\Resources\API\TaxResource;
use App\Http\Resources\API\ShopResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\CouponResource;
use App\Http\Resources\API\ServiceResource;
use App\Models\ProviderServiceAddressMapping;
use App\Http\Resources\API\ProviderTaxResource;
use App\Http\Resources\API\ServiceAddonResource;
use App\Http\Resources\API\BookingRatingResource;
use App\Http\Resources\API\ServiceDetailResource;
use App\Http\Resources\API\UserFavouriteResource;
use Laravel\Sanctum\PersonalAccessToken;
class ServiceController extends Controller
{
    use ZoneTrait;
    public function getServiceList(Request $request)
    {

        $headerValue = $headerValue = $request->header('language-code') ?? session()->get('locale', 'en');
        $lat = $request->latitude ?? session()->get('user_lat', null);
        $lng = $request->longitude ?? session()->get('user_lng', null);
        $service = Service::where('service_type', 'service')->with(['providers', 'category', 'serviceRating', 'translations'])->orderBy('created_at', 'desc');
        $category = Category::onlyTrashed()->get();
        $category = $category->pluck('id');
        $service = $service->whereNotIn('category_id', $category);
        // $service->where('service_request_status' ,0);

        if ($request->has('request_status') && $request->request_status !== '' && $request->request_status !== 'all') {

            if ($request->request_status == 'reject') {
                $service->where('service_request_status', "reject");
            } elseif ($request->request_status == 'approve') {
                $service->where('service_request_status', "approve");
            } else if ($request->request_status == 'pending') {

                $service->where('service_request_status', "pending");
            }
        }


        if (auth()->user() !== null && auth()->user()->hasRole('admin')) {
            $service = $service->withTrashed();
        } elseif (auth()->user() !== null && auth()->user()->hasRole('provider')) {
            $service = $service;
        } else {
            $service = $service->where('status', 1);
        }
        if ($request->has('status') && isset($request->status)) {
            $service->where('status', $request->status);
        }

        if ($request->has('provider_id')) {
            $service->where('provider_id', $request->provider_id);
        }

        if ($request->has('category_id') && $request->category_id != 'null') {
            $service->where('category_id', $request->category_id);
        }
        if ($request->has('subcategory_id') && $request->subcategory_id != '') {
            $service->whereIn('subcategory_id', explode(',', $request->subcategory_id));
        }
        if ($request->has('is_featured')) {
            $service->where('is_featured', $request->is_featured);
        }
        if ($request->has('is_discount')) {
            $service->where('discount', '>', 0)->orderBy('discount', 'desc');
        }
        if ($request->has('is_rating') && $request->is_rating != '') {
            $isRating = (int) $request->is_rating;

            $service->whereHas('serviceRating', function ($q) use ($isRating) {
                $q->select('service_id', \DB::raw('round(AVG(rating), 1) as total_rating'))
                    ->groupBy('service_id')
                    ->havingRaw('total_rating >= ? AND total_rating < ?', [$isRating, $isRating + 1]);
                return $q;
            });
        }


        if ($request->has('is_price_min') && $request->is_price_min != '' || $request->has('is_price_max') && $request->is_price_max != '') {
            $service->whereBetween('price', [$request->is_price_min, $request->is_price_max]);
        }
        if ($request->has('city_id')) {
            $service->whereHas('providers', function ($a) use ($request) {
                $a->where('city_id', $request->city_id);
            });
        }

        if ($request->has('provider_id') && $request->provider_id != '') {

            $service->whereHas('providers', function ($a) use ($request) {
                $a->where('status', 1);
            });

            if (default_earning_type() === 'subscription') {

                $service->whereHas('providers', function ($a) use ($request) {
                    $a->where('status', 1)->where('is_subscribe', 1);
                });
            }
        } else {
            if (default_earning_type() === 'subscription') {
                if (auth()->user() !== null && !auth()->user()->hasRole('admin')) {
                    $service->whereHas('providers', function ($a) use ($request) {
                        $a->where('status', 1)->where('is_subscribe', 1);
                    });
                } else {
                    $service->whereHas('providers', function ($a) use ($request) {
                        $a->where('status', 1)->where('is_subscribe', 1);
                    });
                }
            }
        }
        if ($request->has('search') && !empty(trim($request->search))) {
            $search = trim($request->search);
            $service->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('translations', function($q) use ($search) {
                        $q->where('attribute', 'name')
                          ->whereRaw('LOWER(value) LIKE ?', ['%' . strtolower($search) . '%']);
                    });
            });
        }
      
        $hasCoordinates = !empty($lat) && !empty($lng);
        $locationEnabledInput = $request->input('location_enabled');
        $locationEnabledFlag = filter_var($locationEnabledInput, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $locationEnabled = $hasCoordinates && ($locationEnabledFlag ?? true);

        if ($locationEnabled && !($request->has('search') && !empty(trim($request->search)))) {


            $serviceZone = ServiceZone::all();

            if (count($serviceZone) > 0) {

                try {

                    $matchingZoneIds = $this->getMatchingZonesByLatLng($lat, $lng);

                    $service->whereHas('serviceZoneMapping', function ($service) use ($matchingZoneIds) {
                        $service->whereIn('zone_id', $matchingZoneIds);
                    });
                } catch (\Exception $e) {
                    $service = $service;
                }
            } else {

                $get_distance = getSettingKeyValue('site-setup', 'radious') ?? 50;
                $get_unit = getSettingKeyValue('site-setup', 'distance_type') ?? 'km';

                $locations = $service->locationService($lat, $lng, $get_distance, $get_unit);
                $service_in_location = ProviderServiceAddressMapping::whereIn('provider_address_id', $locations)->get()->pluck('service_id');
                $service->with('providerServiceAddress')->whereIn('id', $service_in_location);
            }
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $service->count();
            }
        }

        if (auth()->user() !== null && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('provider'))) {
            $service = $service->orderBy('created_at', 'desc');
        } else {

            $service = $service->where('status', 1)->orderBy('created_at', 'desc');
        }

        $service = $service->paginate($per_page);

        $items = ServiceResource::collection($service);


        $userservices = null;
        if ($request->customer_id != null) {
            $user_service = Service::where('status', 1)->where('added_by', $request->customer_id)->get();
            $userservices = ServiceResource::collection($user_service);
        }
        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
            'user_services' => $userservices,
            'max' => $service->max('price'),
            'min' => $service->min('price'),
        ];



        return comman_custom_response($response);
    }

    public function getServiceDetail(Request $request)
    {
        $id = $request->service_id;
        $headerValue = $request->header('language-code') ?? session()->get('locale', 'en');
        
        $user = $this->getAuthUserFromToken($request);
        if (auth()->user() !== null) {
            if (auth()->user()->hasRole('admin')) {
                $service = Service::where('service_type', 'service')->withTrashed()->with('providers', 'category', 'serviceRating', 'serviceAddon', 'translations', 'zones','servicePackage','shops')->findorfail($id);
            } else {
                $service = Service::where('service_type', 'service')->where('status', 1)->with('providers', 'category', 'serviceRating', 'serviceAddon', 'translations', 'zones', 'servicePackage', 'shops')->findorfail($id);
            }
        }elseif ($user && $user->user_type === 'provider') { 
             $service = Service::where('service_type', 'service')->with('providers', 'category', 'serviceRating', 'serviceAddon', 'translations', 'zones', 'servicePackage', 'shops')->findorfail($id);
        }else{           
            $service = Service::where('service_type', 'service')->where('status', 1)->with('providers', 'category', 'serviceRating', 'serviceAddon', 'translations', 'zones', 'servicePackage', 'shops')->find($id);
        }
        
        if (empty($service)) {
            $message = __('messages.record_not_found');
            return comman_message_response($message, 406);
        }

        $service_detail = new ServiceDetailResource($service);

        $related = $service->where('service_type', 'service')
            ->where('category_id', $service->category_id)
            ->where('status', 1)
            ->where('service_request_status', 'approve')
            ->where('id', '!=', $service->id); // Exclude self


        $lat = $request->latitude ?? session()->get('user_lat', null);
        $lng = $request->longitude ?? session()->get('user_lng', null);


        $isSubscription = default_earning_type() === 'subscription';
        $hasLocation = $lat && !empty($lat) && $lng && !empty($lng);

        $providerFilter = function ($query) use ($isSubscription) {
            $query->where('status', 1);
            if ($isSubscription) {
                $query->where('is_subscribe', 1);
            }
        };

        if ($hasLocation) {

            if ($lat && !empty($lat) && $lng && !empty($lng)) {

                $serviceZone = ServiceZone::all();

                if (count($serviceZone) > 0) {

                    try {
                        $distanceLimit = getSettingKeyValue('site-setup', 'radious');
                        $unit = getSettingKeyValue('site-setup', 'distance_type');

                        $allServices = $service->where('service_type', 'service')
                            ->where('category_id', $service->category_id)
                            ->where('status', 1)
                            ->where('id', '!=', $service->id)->get();

                        $matchingServiceIds = [];

                        foreach ($allServices as $serv) {
                            $matchingZoneIds = $this->getNearbyZoneserviceIds($serv->id, $lat, $lng);
                            if (!empty($matchingZoneIds)) {
                                $matchingServiceIds[] = $serv->id;
                            }
                        }

                        if (!empty($matchingServiceIds)) {
                            $related = $related->whereIn('id', $matchingServiceIds);
                        } else {
                            $related = $related->whereRaw('1 = 0'); // force empty result

                        }
                        $related = $related->get();
                    } catch (\Exception $e) {
                        $related = $related->whereRaw('1 = 0');
                        $related = $related->get();
                    }
                }
            }
        } else {
            // Without location
            $related->whereHas('providers', $providerFilter);
            $related = $related->get();
        }

        $related_service = ServiceResource::collection($related);

        $rating_data = BookingRatingResource::collection($service_detail->serviceRating->take(5));

        $customer_reviews = [];
        if ($request->customer_id != null) {
            $customer_review = BookingRating::where('customer_id', $request->customer_id)->where('service_id', $id)->get();
            if (!empty($customer_review)) {
                $customer_reviews = BookingRatingResource::collection($customer_review);
            }
        }

        $coupon = Coupon::with('serviceAdded')
            ->where('expire_date', '>', date('Y-m-d H:i'))
            ->where('status', 1)
            ->whereHas('serviceAdded', function ($coupon) use ($id) {
                $coupon->where('service_id', $id);
            })->get();
        $coupon_data = CouponResource::collection($coupon);
        $tax = ProviderTaxMapping::with('taxes')->where('provider_id', $service->provider_id)->get()->filter(function ($item) {
            return $item->taxes !== null && optional($item->taxes)->status == 1;
        });
        $taxes = ProviderTaxResource::collection($tax);
        // $tax = Tax::where('status',1)->get();
        // $taxes = TaxResource::collection($tax);
        $servicefaq = ServiceFaq::where('service_id', $id)->where('status', 1)->get();
        $serviceAddon = ServiceAddon::where('service_id', $id)->where('status', 1)->get();
        $serviceaddon = ServiceAddonResource::collection($serviceAddon);

        // Get zones data
        $zones = $service->zones()
            ->where('service_zones.status', 1)
            ->select('service_zones.id', 'service_zones.name', 'service_zones.coordinates')
            ->get()
            ->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    // 'coordinates' => $zone->coordinates
                ];
            });

        $shopQuery = Shop::query()->where('is_active', 1)->with('shopHours');

        if ($request->has('service_id') && !empty($request->service_id)) {
            $shopQuery->whereHas('services', function ($query) use ($request) {
                $query->where('service_id', $request->service_id);
            });
        }


        $shops = ShopResource::collection($shopQuery->take(5)->get());
        $user_points = null;
        if ($request->customer_id) {
            $user_points = User::where('user_type', 'user')->find($request->customer_id)->loyalty_points;
        }

        if ($id) {
            $redeem_points = LoyaltyRedeemRule::resolveRedeemRulesByService($id);
        }

        $response = [
            'service_detail' => $service_detail,
            'provider' => new UserResource(optional($service->providers)),
            'rating_data' => $rating_data,
            'customer_review' => $customer_reviews,
            'coupon_data' => $coupon_data,
            'taxes' => $taxes,
            'related_service' => $related_service,
            'service_faq' => $servicefaq,
            'serviceaddon' => $serviceaddon,
            'zones' => $zones,
            'shop' => $shops,
            'user_points' => $user_points,
            'redeem_points' => $redeem_points,
        ];

        return comman_custom_response($response);
    }

    public function getServiceRating(Request $request)
    {

        $rating_data = BookingRating::where('service_id', $request->service_id);

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $rating_data->count();
            }
        }

        $rating_data = $rating_data->orderBy('id', 'desc')->paginate($per_page);
        $items = BookingRatingResource::collection($rating_data);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);
    }
    public function saveFavouriteService(Request $request)
    {
        $user_favourite = $request->all();

        $result = UserFavouriteService::updateOrCreate(['id' => $request->id], $user_favourite);

        $message = __('messages.update_form', ['form' => __('messages.wishlist')]);
        if ($result->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.wishlist')]);
        }

        return comman_message_response($message);
    }

    public function deleteFavouriteService(Request $request)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => __('messages.action_is_unauthorized')], 401);
        }

        $serviceId = $request->input('service_id');
        if (empty($serviceId) || !is_numeric($serviceId)) {
            return response()->json(['message' => __('messages.record_not_found')], 422);
        }

        UserFavouriteService::where('user_id', $userId)->where('service_id', (int) $serviceId)->delete();

        $message = __('messages.delete_form', ['form' => __('messages.wishlist')]);

        return comman_message_response($message);
    }

    public function getUserFavouriteService(Request $request)
    {
        $user = auth()->user();

        $favourite = UserFavouriteService::where('user_id', $user->id);

        $per_page = config('constant.PER_PAGE_LIMIT');

        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $favourite->count();
            }
        }

        $favourite = $favourite->whereHas('service', function ($query) {
                $query->where('status', 1);
        });

        if (default_earning_type() === 'subscription') {
            $favourite = $favourite->whereHas('service.providers', function ($query) {
                $query->where('status', 1)->where('is_subscribe', 1);
            });
        }
        $favourite = $favourite->orderBy('created_at', 'desc')->paginate($per_page);

        $items = UserFavouriteResource::collection($favourite);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);
    }
    public function getTopRatedService()
    {
        $rating_data = BookingRating::whereNotNull('review')->orderBy('rating', 'desc')->limit(5)->get();
        $items = BookingRatingResource::collection($rating_data);

        $response = [
            'data' => $items,
        ];

        return comman_custom_response($response);
    }
    public function serviceReviewsList(Request $request)
    {
        $id = $request->service_id;
        $rating_data = BookingRating::where('service_id', $id);

        $per_page = config('constant.PER_PAGE_LIMIT');

        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $rating_data->count();
            }
        }

        $rating_data = $rating_data->orderBy('created_at', 'desc')->paginate($per_page);

        $items = BookingRatingResource::collection($rating_data);
        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];
        return comman_custom_response($response);
    }
    public function saveServiceCoupon(Request $request)
    {
        $data = $request->all();

        $data['expire_date'] = isset($request->expire_date) ? date('Y-m-d H:i:s', strtotime($request->expire_date)) : date('Y-m-d H:i:s');
        $result = Coupon::updateOrCreate(['id' => $data['id']], $data);
        if ($result) {
            $service_data = [
                'coupon_id' => $result->id,
                'service_id' => $service
            ];
            CouponServiceMapping::Create($service_data);
        }
        $message = trans('messages.update_form', ['form' => trans('messages.coupon')]);
        if ($result->wasRecentlyCreated) {
            $message = trans('messages.save_form', ['form' => trans('messages.coupon')]);
        }
        return comman_message_response($message);
    }


    public function servicestatus(Request $request)
    {

        $validatedData = $request->validate([
            'id' => 'required|integer',
            'request_status' => 'required|string',
            'reject_reason' => 'nullable|string',
        ]);


        $service = Service::find($validatedData['id']);

        $service = Service::withTrashed()->find($validatedData['id']);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $requestStatus = $validatedData['request_status'];


        if ($requestStatus === 'delete') {

            if ($service->trashed()) {
                return response()->json(['message' => 'Service is already soft deleted. Use "permanently_delete" to delete permanently.'], 400);
            }

            $service->delete();
            return response()->json(['message' => 'Service soft deleted successfully'], 200);
        }

        if ($requestStatus === 'permanently_delete') {
            if ($service->trashed()) {
                $service->forceDelete();
                return response()->json(['message' => 'Service permanently deleted successfully'], 200);
            }

            return response()->json(['message' => 'Service is not soft deleted. Use "delete" first.'], 400);
        }


        if ($requestStatus === 'restore') {
            if ($service->trashed()) {
                \Log::info('Service restore attempt', [
                    'service_id' => $service->id,
                    'provider_id' => $service->provider_id,
                    'earning_type' => default_earning_type()
                ]);
                
                // Check if provider has an active subscription
                if (default_earning_type() === 'subscription') {
                    if (is_any_plan_active($service->provider_id) != 1) {
                        \Log::warning('Restore blocked - no active plan', [
                            'provider_id' => $service->provider_id
                        ]);
                        return response()->json([
                            'status' => false,
                            'message' => __('messages.no_active_plan_error')
                        ], 400);
                    }
                    
                    // Get plan limit
                    $active_plan = get_user_active_plan($service->provider_id);
                    \Log::info('Active plan details', [
                        'plan_type' => $active_plan ? $active_plan->plan_type : 'none',
                        'plan_id' => $active_plan ? $active_plan->id : null
                    ]);
                    
                    if ($active_plan && $active_plan->plan_type === 'limited') {
                        $plan_limitation = is_array($active_plan->plan_limitation) 
                            ? $active_plan->plan_limitation 
                            : json_decode($active_plan->plan_limitation, true) ?? [];
                        
                        \Log::info('Plan limitations', [
                            'plan_limitation' => $plan_limitation
                        ]);
                        
                        if (isset($plan_limitation['service']) && 
                            $plan_limitation['service']['is_checked'] === 'on' && 
                            isset($plan_limitation['service']['limit'])) {
                            
                            $service_limit = (int)$plan_limitation['service']['limit'];
                            
                            // Count ALL non-deleted services (excluding the soft-deleted ones)
                            // Using withoutTrashed() to explicitly exclude soft-deleted records
                            $total_service_count = \App\Models\Service::withoutTrashed()
                                ->where('provider_id', $service->provider_id)
                                ->count();
                            
                            \Log::info('Service count check', [
                                'current_non_deleted_count' => $total_service_count,
                                'limit' => $service_limit,
                                'after_restore_would_be' => $total_service_count + 1,
                                'would_exceed' => ($total_service_count + 1) > $service_limit
                            ]);
                            
                            // Check if restoring would exceed the total limit
                            // After restore, total will be: current_count + 1 (the restored service)
                            if (($total_service_count + 1) > $service_limit) {
                                \Log::warning('Restore blocked - limit exceeded', [
                                    'current_count' => $total_service_count,
                                    'limit' => $service_limit,
                                    'would_result_in' => $total_service_count + 1
                                ]);
                                return response()->json([
                                    'status' => false,
                                    'message' => __('messages.service_restore_limit_exceeded', [
                                        'limit' => $service_limit
                                    ])
                                ], 400);
                            }
                        }
                    }
                }
                
                \Log::info('Service restore allowed - proceeding', [
                    'service_id' => $service->id
                ]);
                
                $service->restore();
                return response()->json([
                    'status' => true,
                    'message' => __('messages.msg_restored', ['name' => __('messages.service')])
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => __('messages.service_not_deleted')
                ], 400);
            }
        }




        $statusMapping = ['pending', 'approve', 'reject'];
        if (!in_array($requestStatus, $statusMapping)) {
            return response()->json(['message' => 'Invalid request status'], 400);
        }


        $service->service_request_status = $requestStatus;


        if ($requestStatus === 'reject') {
            $service->reject_reason = $validatedData['reject_reason'] ?? 'No reason provided';
        } else {
            $service->reject_reason = null;
        }

        $service->save();

        return response()->json(['message' => 'Service updated successfully'], 200);
    }
    private function getAuthUserFromToken($request)
{
    $token = $request->bearerToken();

    if (!$token) return null;

    $accessToken = PersonalAccessToken::findToken($token);

    return $accessToken ? $accessToken->tokenable : null;
}
   
    /**
     * Provider can activate/deactivate their own service.
     * Input:
     *  - service_id (required)
     *  - status (required): 1 => set 1, else => set 0
     */
    public function providerServiceStatus(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
            'status' => 'required',
        ]);

        $providerId = auth()->id();
        $serviceId = (int) $validated['service_id'];

        $service = Service::where('provider_id', $providerId)
            ->where('id', $serviceId)
            ->where('service_type', 'service')
            ->first();

        if (!$service) {
            return comman_custom_response([
                'status' => false,
                'message' => 'Service not found',
            ], 404);
        }

        $newStatus = ((int) $validated['status'] === 1) ? 1 : 0;

        // Enforce plan limits when activating service
        if ($newStatus === 1 && (int) $service->status !== 1) {
            $isFeatured = ((int) $service->is_featured === 1);
            $validation = canActivateService($providerId, $serviceId, $isFeatured);

            if (!$validation['can_activate']) {
                return comman_custom_response([
                    'status' => false,
                    'message' => $validation['message'],
                    'service_id' => $serviceId,
                ], 200);
            }
        }

        $service->status = $newStatus;
        $service->save();

        return comman_custom_response([
            'status' => true,
            'message' => __('messages.service_status_updated'),
            'service_id' => $serviceId,
            'status' => $newStatus,
        ]);
    }
}
