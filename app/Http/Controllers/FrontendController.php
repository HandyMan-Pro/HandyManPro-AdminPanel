<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Blog;
use App\Models\Shop;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Category;
use App\Models\HelpDesk;
use App\Traits\ZoneTrait;
use App\Models\ServiceZone;
use App\Models\SubCategory;
use App\Models\ServiceAddon;
use Illuminate\Http\Request;
use App\Models\BookingRating;
use  App\Models\WalletHistory;
use App\Models\HandymanRating;
use App\Models\PostJobRequest;
use App\Models\ServicePackage;
use App\Models\FrontendSetting;
use App\Models\LoyaltyEarnRule;
use App\Traits\TranslationTrait;
use Yajra\DataTables\DataTables;
use App\Models\LoyaltyRedeemRule;
use App\Models\PromotionalBanner;
use App\Models\ProviderTaxMapping;
use App\Models\LoyaltyReferralRule;
use App\Models\ProviderZoneMapping;
use App\Models\LoyaltyPointActivity;
use App\Models\UserFavouriteService;
use App\Models\BookingHandymanMapping;
use App\Models\ProviderAddressMapping;
use App\Models\LoyaltyEarnServiceMapping;
use App\Http\Controllers\API\BlogController;
use App\Models\ProviderServiceAddressMapping;
use App\Http\Resources\API\ShopDetailResource;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\HelpDeskController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\PostJobRequestController;
use App\Models\Payment;

class FrontendController extends Controller
{
    use TranslationTrait;
    use ZoneTrait;
    public function index(Request $request)
    {
        $auth_user_id = null;
        $favourite = null;

        if (auth()->check() && auth()->user()->hasRole('user')) {
            $auth_user_id = auth()->user()->id;
            $favourite = UserFavouriteService::where('user_id', $auth_user_id)->get();
        }
        $sectionData = [];
        $sectionKeys = ['section_1', 'section_2', 'section_3', 'section_4', 'section_5', 'section_6', 'section_7', 'section_8', 'section_9'];

        $primary_locale = session()->get('locale', 'en');

        foreach ($sectionKeys as $key) {
            $section = FrontendSetting::where('key', $key)->first();

            if (!$section) {
                $sectionData[$key] = null;
                continue;
            }

            $data = $section ? json_decode($section->value, true) : null;

            $translations =  $section->translations ?? [];

            $data['title'] = $this->getTranslation($translations, $primary_locale, 'title', $data['title'] ?? '');
            $data['description'] = $this->getTranslation($translations, $primary_locale, 'description', $data['description'] ?? '');
            $sectionData[$key] = $data;
        }
        $status = FrontendSetting::where('key', 'section_1')->first();
        $settings = Setting::where('type', 'service-configurations')->where('key', 'service-configurations')->first();
        $serviceconfig = $settings ? json_decode($settings->value) : null;
        $postjobservice = $serviceconfig ? $serviceconfig->post_services : null;
        $ratings = BookingRating::pluck('rating')->toArray();
        $averageRating = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
        $totalRating = number_format($averageRating, 2);
        $promotional_banners = PromotionalBanner::where('status', 'accepted')->where('payment_status', 'paid')->where('start_date', '<=', now())->where('end_date', '>=', now()->startOfDay())->get();
        $shops = $this->getShops($request);
        $language_array = $this->languagesArray();

        // Nearby Providers enabled/disabled toggle (Other Settings)
        $otherSettingRow      = Setting::where('type', 'OTHER_SETTING')->where('key', 'OTHER_SETTING')->first();
        $otherSettingData     = $otherSettingRow ? json_decode($otherSettingRow->value) : null;
        $nearbyProviderEnabled = ($otherSettingData && isset($otherSettingData->nearby_provider) && $otherSettingData->nearby_provider == 1) ? 1 : 0;

        return view('landing-page.index', compact('promotional_banners', 'sectionData', 'postjobservice', 'auth_user_id', 'favourite', 'totalRating', 'status', 'shops', 'language_array', 'serviceconfig', 'nearbyProviderEnabled'));
    }

    public function getShops(Request $request)
    {
        $latitude = $request->latitude ?? session('user_lat');
        $longitude = $request->longitude ?? session('user_lng');
        $lat = !empty($latitude) ? $latitude : null;
        $long = !empty($longitude) ? $longitude : null;

        $query = Shop::where('is_active', 1)->nearByShop($lat, $long);
        if (default_earning_type() === 'subscription') {
            $query->whereHas('provider', function ($a) {
                $a->where('status', 1)->where('is_subscribe', 1);
            });
        }


        $shops = $query->latest()->take(10)->get();

        return $shops;
    }

    public function userLoginView(Request $request)
    {
        $footerSection = FrontendSetting::where('key', 'login-register-setting')->first();
        $sectionData = $footerSection ? json_decode($footerSection->value, true) : null;

        $primary_locale = session()->get('locale', 'en');

        $translations =  $footerSection->translations ?? [];

        $sectionData['title'] = $this->getTranslation($translations, $primary_locale, 'title', $sectionData['title'] ?? '');
        $sectionData['description'] = $this->getTranslation($translations, $primary_locale, 'description', $sectionData['description'] ?? '');

        $sucess = "hello";
        return view('landing-page.login', compact('sectionData', 'sucess'));
    }

    public function partnerRegistrationView(Request $request)
    {
        $footerSection = FrontendSetting::where('key', 'login-register-setting')->first();
        $sectionData = $footerSection ? json_decode($footerSection->value, true) : null;

        $primary_locale = session()->get('locale', 'en');
        $translations = $footerSection ? ($footerSection->translations ?? []) : [];
        if ($sectionData) {
            $sectionData['title'] = $this->getTranslation($translations, $primary_locale, 'title', $sectionData['title'] ?? '');
            $sectionData['description'] = $this->getTranslation($translations, $primary_locale, 'description', $sectionData['description'] ?? '');
        }

        $othersetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();
        $nearby_provider = 0;
        if ($othersetting) {
            $decoded = json_decode($othersetting->value, true);
            $nearby_provider = $decoded['nearby_provider'] ?? 0;
        }

        return view('landing-page.providerRegister', compact('sectionData', 'nearby_provider'));
    }

    public function userRegistrationView(Request $request)
    {
        $footerSection = FrontendSetting::where('key', 'login-register-setting')->first();
        $sectionData = $footerSection ? json_decode($footerSection->value, true) : null;

        $primary_locale = session()->get('locale', 'en');
        $translations = $footerSection ? ($footerSection->translations ?? []) : [];
        if ($sectionData) {
            $sectionData['title'] = $this->getTranslation($translations, $primary_locale, 'title', $sectionData['title'] ?? '');
            $sectionData['description'] = $this->getTranslation($translations, $primary_locale, 'description', $sectionData['description'] ?? '');
        }

        $othersetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();
        $nearby_provider = 0;
        if ($othersetting) {
            $decoded = json_decode($othersetting->value, true);
            $nearby_provider = $decoded['nearby_provider'] ?? 0;
        }

        return view('landing-page.register', compact('sectionData', 'nearby_provider'));
    }

    public function forgotPassword(Request $request)
    {
        $footerSection = FrontendSetting::where('key', 'login-register-setting')->first();
        $sectionData = $footerSection ? json_decode($footerSection->value, true) : null;

        $primary_locale = session()->get('locale', 'en');
        $translations = $footerSection ? ($footerSection->translations ?? []) : [];
        if ($sectionData) {
            $sectionData['title'] = $this->getTranslation($translations, $primary_locale, 'title', $sectionData['title'] ?? '');
            $sectionData['description'] = $this->getTranslation($translations, $primary_locale, 'description', $sectionData['description'] ?? '');
        }

        return view('landing-page.forgot-password', compact('sectionData'));
    }

    public function catgeoryList(Request $request)
    {
        $headerSection = FrontendSetting::where('key', 'heder-menu-setting')->first();
        $sectionData = $headerSection ? json_decode($headerSection->value, true) : null;

        if ($sectionData['categories'] == 0) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }

        return view('landing-page.category');
    }

    public function subCatgeoryList(Request $request)
    {
        $category_id = $request->category_id;
        return view('landing-page.sub-category', compact('category_id'));
    }

    public function serviceList(Request $request)
    {
        $headerSection = FrontendSetting::where('key', 'heder-menu-setting')->first();
        $sectionData = $headerSection ? json_decode($headerSection->value, true) : null;

        if ($sectionData['service'] == 0) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }

        if ($request->provider_id) {
            $id = $request->provider_id;
            $type = "provider-service";
        } else if ($request->subcategory_id) {
            $id = $request->subcategory_id;
            $type = 'subcategory-service';
        } else if ($request->category_id) {
            $id = $request->category_id;
            $type = 'category-service';
        } else if ($request->shop_id) {
            $id = $request->shop_id;
            $type = 'shop-service';
        } else {
            $id = null;
            $type = null;
        }

        if ($request->latitude && $request->longitude) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
        } else {
            $latitude = null;
            $longitude = null;
        }
        return view('landing-page.service', compact('id', 'type', 'latitude', 'longitude'));
    }

    public function shopList(Request $request)
    {
        if ($request->service_id) {
            $id = $request->service_id;
            $type = "on_shop_service";
        } else if ($request->provider_id) {
            $id = $request->provider_id;
            $type = "provider_shops";
        } else {
            $id = null;
            $type = null;
        }

        $lat = $request->latitude ?? session('user_lat');
        $lng = $request->longitude ?? session('user_lng');

        $latitude = !empty($lat) ? $lat : null;
        $longitude = !empty($lng) ? $lng : null;

        return view('landing-page.shop', compact('id', 'type', 'latitude', 'longitude'));
    }

    public function providerList(Request $request)
    {
        $headerSection = FrontendSetting::where('key', operator: 'heder-menu-setting')->first();
        $sectionData = $headerSection ? json_decode($headerSection->value, true) : null;
        $lat = $request->latitude ?? session('user_lat');
        $lng = $request->longitude ?? session('user_lng');
        if ($sectionData['provider'] == 0) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }
        if ($lat && !empty($lat) && $lng && !empty($lng)) {
            $latitude = $lat;
            $longitude = $lng;
        } else {
            $latitude = null;
            $longitude = null;
        }
        return view('landing-page.provider', compact('latitude', 'longitude'));
    }

    public function nearbyProviders(Request $request)
    {
        // Guard: redirect home if the Nearby Providers feature is disabled
        $otherSettingRow  = Setting::where('type', 'OTHER_SETTING')->where('key', 'OTHER_SETTING')->first();
        $otherSettingData = $otherSettingRow ? json_decode($otherSettingRow->value) : null;
        if (!$otherSettingData || empty($otherSettingData->nearby_provider)) {
            return redirect()->route('frontend.index');
        }

        $sitesetup     = Setting::getValueByKey('site-setup', 'site-setup');
        $maxRadius     = (isset($sitesetup->radious) && $sitesetup->radious > 0) ? (int) $sitesetup->radious : 100;
        $distanceUnit  = (isset($sitesetup->distance_type) && !empty($sitesetup->distance_type)) ? $sitesetup->distance_type : 'km';
        $initialRadius = min(25, $maxRadius);   // 25 km default — covers all NYC metro providers
        $defaultLat    = (isset($sitesetup->latitude)  && $sitesetup->latitude  != ''  && $sitesetup->latitude  !== null) ? (float) $sitesetup->latitude  : 40.7128;
        $defaultLng    = (isset($sitesetup->longitude) && $sitesetup->longitude != '' && $sitesetup->longitude !== null) ? (float) $sitesetup->longitude : -74.0060;

        // Use explicit request/session coords if provided, otherwise fall back to site defaults.
        // This ensures serverLat is always a valid number in JS — prevents double-fetch bugs.
        $lat = $request->latitude ?? session('user_lat');
        $lng = $request->longitude ?? session('user_lng');
        $latitude  = ($lat && !empty($lat)) ? (float) $lat : $defaultLat;
        $longitude = ($lng && !empty($lng)) ? (float) $lng : $defaultLng;

        $categories = \App\Models\Category::where('status', 1)->orderBy('name')->get();

        return view('landing-page.nearby-providers', compact('latitude', 'longitude', 'categories', 'maxRadius', 'distanceUnit', 'initialRadius', 'defaultLat', 'defaultLng'));
    }
    public function List(Request $request)
    {
        $headerSection = FrontendSetting::where('key', 'heder-menu-setting')->first();
        $sectionData = $headerSection ? json_decode($headerSection->value, true) : null;
        $lat = $request->latitude ?? session('user_lat');
        $lng = $request->longitude ?? session('user_lng');
        if ($sectionData['shop'] == 0) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }
        if ($lat && !empty($lat) && $lng && !empty($lng)) {
            $latitude = $lat;
            $longitude = $lng;
        } else {
            $latitude = null;
            $longitude = null;
        }
        return view('landing-page.shop', compact('latitude', 'longitude'));
    }
    public function blogList(Request $request)
    {
        $settings = Setting::whereIn('type', ['service-configurations', 'OTHER_SETTING'])
            ->whereIn('key', ['service-configurations', 'OTHER_SETTING'])
            ->get()
            ->keyBy('type');
        $othersetting = $settings->has('OTHER_SETTING') ? json_decode($settings['OTHER_SETTING']->value) : null;

        if (optional($othersetting)->blog  == 0) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }

        return view('landing-page.blog');
    }

    public function bookingList(Request $request)
    {
        $headerSection = FrontendSetting::where('key', 'heder-menu-setting')->first();
        $sectionData = $headerSection ? json_decode($headerSection->value, true) : null;

        if ($sectionData['bookings'] == 0) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }

        return view('landing-page.booking');
    }

    public function postJobList(Request $request)
    {
        return view('landing-page.post-job');
    }

    public function relatedService(Request $request)
    {
        $service_id = $request->id;
        $serviceController = app(ServiceController::class);
        $apiRequest = new Request(['service_id' => $service_id, 'per_page' => 'all']);
        $service = $serviceController->getServiceDetail($apiRequest);
        $serviceData = json_decode($service->content(), true);
        return view('landing-page.related-service', compact('service'));
    }

    public function categoryDetail(Request $request)
    {
        $category_id = $request->id;
        $primary_locale = session()->get('locale', 'en');
        $category = Category::where('id', $category_id)->first();
        if (!$category) {
            abort(404, 'Category not found');
        }
        $category->name = $this->getTranslation($category->translations, $primary_locale, 'name', $category->name) ?? $category->name;
        $category->description = $this->getTranslation($category->translations, $primary_locale, 'description', $category->description) ?? $category->description;
        $sub_category = SubCategory::with('translations')
            ->where('category_id', $category_id)
            ->where('status', 1)
            ->get()
            ->map(function ($subCategory) use ($primary_locale) {
                $subCategory->name = $this->getTranslation($subCategory->translations, $primary_locale, 'name', $subCategory->name) ?? $subCategory->name;

                return $subCategory;
            });
        $serviceController = app(ServiceController::class);
        $apiRequest = new Request(['category_id' => $category_id, 'per_page' => 'all']);


        if (session()->has('user_lat') && session()->has('user_lng')) {
            $apiRequestData['lat'] = session('user_lat');
            $apiRequestData['lng'] = session('user_lng');
        }
        // Set the language code header


        $apiRequest->headers->set('language-code', $primary_locale);
        $serviceResponse = $serviceController->getServiceList($apiRequest);
        $service = $serviceResponse->getData()->data ?? [];
        $service = array_filter($service, function ($item) {
            return isset($item->service_request_status) && $item->service_request_status === 'approve';
        });
        return view('landing-page.category-detail', compact('category', 'sub_category', 'service'));
    }

    public function blogDetail(Request $request)
    {
        $blog_id = $request->id;
        $blog_data = Blog::all();
        $blogController = app(BlogController::class);
        $apiRequest = new Request(['blog_id' => $blog_id]);
        $blogResponse = $blogController->getBlogDetail($apiRequest);
        $blog = $blogResponse->getData()->blog_detail ?? [];
        $blog->read_time = calculateReadingTime($blog->description);

        return view('landing-page.blog-detail', compact('blog', 'blog_data'));
    }

    public function providerDetail(Request $request)
    {
        $provider_id = $request->id;
        $userController = app(UserController::class);
        $apiRequest = new Request(['id' => $provider_id]);
        $providerData = $userController->userDetail($apiRequest);
        $providerData = json_decode($providerData->content(), true);
        $why_choose_me = json_decode($providerData['data']['why_choose_me'], true);

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $datetime = json_decode($sitesetup->value);

        $completed_services = Booking::where('provider_id', $providerData['data']['id'])->where('status', 'completed')->count();

        $servicerating = Service::where('provider_id', $providerData['data']['id'])->with('serviceRating')->get();
        $allRatings = $servicerating->flatMap(function ($service) {
            return $service->serviceRating->filter(function ($rating) {
                return in_array($rating->rating, [4, 5]);
            });
        });
        $satisfy_customers = $allRatings->pluck('customer_id')->unique()->count();

        if (!empty(auth()->user()) && auth()->user()->hasRole('user')) {
            $auth_user_id = auth()->user()->id;
            $favourite = UserFavouriteService::where('user_id', $auth_user_id)->get();
        } else {
            $auth_user_id = null;
            $favourite = null;
        }

        $shoplist = Shop::where('provider_id', $providerData['data']['id'])->where('is_active', 1)->get();

        return view('landing-page.ProviderDetails', compact('providerData', 'why_choose_me', 'datetime', 'completed_services', 'satisfy_customers', 'auth_user_id', 'favourite', 'shoplist'));
    }

    public function handymanDetail(Request $request)
    {
        $handyman_id = $request->id;
        $userController = app(UserController::class);
        $apiRequest = new Request(['id' => $handyman_id]);
        $handymanData = $userController->userDetail($apiRequest);
        $handymanData = json_decode($handymanData->content(), true);
        $why_choose_me = json_decode($handymanData['data']['why_choose_me'], true);

        $handyman_rating = HandymanRating::where('handyman_id', $handymanData['data']['id'])->orderBy('created_at', 'desc')->get();
        $total_handyman_rating = $handyman_rating->count();

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $datetime = json_decode($sitesetup->value);

        $completed_services = BookingHandymanMapping::whereHas('bookings', function ($query) {
            $query->where('status', 'completed');
        })->where('handyman_id', $handymanData['data']['id'])->count();

        $satisfy_customers = $handyman_rating->filter(function ($rating) {
            return in_array($rating->rating, [4, 5]);
        })->pluck('customer_id')->unique()->count();

        return view('landing-page.HandymanDetails', compact('handymanData', 'why_choose_me', 'handyman_rating', 'total_handyman_rating', 'datetime', 'completed_services', 'satisfy_customers'));
    }

    public function serviceDetail(Request $request, $id)
    {
        $service_id = $id;
        $shop_id = $request->shop_id;
        $serviceController = app(ServiceController::class);
        $apiRequest = new Request(['service_id' => $service_id, 'per_page' => 'all']);
        $service = $serviceController->getServiceDetail($apiRequest);
        $serviceData = json_decode($service->content(), true);

        if (isset($serviceData['message']) && $serviceData['message'] === 'Record not found') {
            abort(404, 'Service not found');
        }

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $date_time = json_decode($sitesetup->value);
        $favouriteServiceData = [];

        if (!empty(auth()->user())) {
            $favouriteServiceResponse = $serviceController->getUserFavouriteService($apiRequest);
            $favouriteService = json_decode($favouriteServiceResponse->content(), true);
            $favouriteServiceData = $favouriteService['data'] ?? [];
            $userId = auth()->user()->id;
            $favouriteService = collect($favouriteServiceData)->filter(function ($item) use ($userId, $service_id) {
                return isset($item['user_id']) && $item['user_id'] == $userId
                    && isset($item['service_id']) && $item['service_id'] == $service_id;
            })->toArray();
        } else {
            $favouriteService = collect();
            $userId = 0;
        }
        $completed_services = Booking::where('provider_id', operator: $serviceData['provider']['id'])->where('status', 'completed')->count();

        $shops = Service::find($service_id)->shops()->where('is_active', 1)->get();
        $knownLanguageArray = json_decode($serviceData['provider']['known_languages'], true);
        $subtotal = $serviceData['service_detail']['discount'] != 0 ? ($serviceData['service_detail']['price']) - ($serviceData['service_detail']['price'] * $serviceData['service_detail']['discount'] / 100) : $subtotal = $serviceData['service_detail']['price'];
        $total_ratings = BookingRating::where('service_id', $serviceData['service_detail']['id'])->get();
        return view('landing-page.ServiceDetail', compact('serviceData', 'favouriteService', 'date_time', 'completed_services', 'knownLanguageArray', 'subtotal', 'total_ratings', 'favouriteServiceData', 'userId', 'shop_id', 'shops'));
    }

    public function shopDetail(Request $request)
    {
        $shopQuery = Shop::where('is_active', 1)->with([
            'country',
            'state',
            'city',
            'provider',
            'shopHours',
            'services' => function ($query) {
                $query->where('status', 1);
            },
            'services.getUserFavouriteService' => function ($query) {
                $query->where('user_id', auth()->id());
            }
        ]);

        if(default_earning_type() === 'subscription') {
            $shopQuery->whereHas('provider', fn($q) => $q->where('is_subscribe', 1));
        }

        $shop = $shopQuery->findOrFail($request->id);

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $date_time = json_decode($sitesetup->value);

        $targetTimezone = isset($date_time->time_zone) ? trim((string) $date_time->time_zone) : 'UTC';
        $timeFormat = $date_time->time_format ?? 'H:i';
        // Use the exact format saved by admin, don't convert it
        $timeFormatDisplay = $timeFormat;

        $shop['shop_start_time'] = $shop->getRawOriginal('shop_start_time')
            ? Carbon::parse($shop->getRawOriginal('shop_start_time'), 'UTC')->setTimezone($targetTimezone)->format($timeFormatDisplay)
            : null;
        $shop['shop_end_time'] = $shop->getRawOriginal('shop_end_time')
            ? Carbon::parse($shop->getRawOriginal('shop_end_time'), 'UTC')->setTimezone($targetTimezone)->format($timeFormatDisplay)
            : null;

        $daysOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $shopHoursFromDb = $shop->shopHours()
            ->orderByRaw("FIELD(day, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->get();

        if ($shopHoursFromDb->isNotEmpty()) {
            $shopHoursOrdered = $shopHoursFromDb->map(function ($h) use ($timeFormatDisplay) {
                $start = $h->start_time ? Carbon::createFromFormat('H:i:s', $h->start_time)->format($timeFormatDisplay) : null;
                if (!$start && $h->start_time) {
                    // Fallback if format doesn't match
                    try {
                        $start = Carbon::parse($h->start_time)->format($timeFormatDisplay);
                    } catch (\Exception $e) {
                        $start = $h->start_time;
                    }
                }

                $end = $h->end_time ? Carbon::createFromFormat('H:i:s', $h->end_time)->format($timeFormatDisplay) : null;
                if (!$end && $h->end_time) {
                    // Fallback if format doesn't match
                    try {
                        $end = Carbon::parse($h->end_time)->format($timeFormatDisplay);
                    } catch (\Exception $e) {
                        $end = $h->end_time;
                    }
                }

                $breaksFormatted = [];
                if (is_array($h->breaks)) {
                    foreach ($h->breaks as $b) {
                        if (isset($b['start_break']) && isset($b['end_break'])) {
                            // Break times are also TIME fields - format directly without timezone conversion
                            try {
                                $bs = Carbon::createFromFormat('H:i:s', $b['start_break'])->format($timeFormatDisplay);
                            } catch (\Exception $e) {
                                try {
                                    $bs = Carbon::parse($b['start_break'])->format($timeFormatDisplay);
                                } catch (\Exception $e2) {
                                    $bs = $b['start_break'];
                                }
                            }

                            try {
                                $be = Carbon::createFromFormat('H:i:s', $b['end_break'])->format($timeFormatDisplay);
                            } catch (\Exception $e) {
                                try {
                                    $be = Carbon::parse($b['end_break'])->format($timeFormatDisplay);
                                } catch (\Exception $e2) {
                                    $be = $b['end_break'];
                                }
                            }

                            if ($bs && $be) {
                                $breaksFormatted[] = ['start' => $bs, 'end' => $be];
                            }
                        }
                    }
                }
                return [
                    'day' => $h->day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_holiday' => $h->is_holiday,
                    'breaks' => $breaksFormatted,
                ];
            });
        }
        else {
            $shopHoursOrdered = collect();
        }
        $completed_services = Booking::where('provider_id', $shop->provider->id)
            ->where('status', 'completed')
            ->count();

        return view('landing-page.ShopDetail', compact(
            'shop',
            'date_time',
            'completed_services',
            'shopHoursOrdered',
            'timeFormatDisplay',
        ));
    }

    public function privacyPolicy(Request $request)
    {
        $headerValue = session()->get('locale', 'en');
        $privacy_content = Setting::getValueByKey('privacy_policy', 'privacy_policy', $headerValue);

        return view('landing-page.PrivacyPolicy', compact('privacy_content'));
    }

    public function termConditions(Request $request)
    {
        $headerValue = session()->get('locale', 'en');
        $terms_content = Setting::getValueByKey('terms_condition', 'terms_condition', $headerValue);

        return view('landing-page.TermConditions', compact('terms_content'));
    }

    public function aboutUs(Request $request)
    {
        $headerValue = session()->get('locale', 'en');
        $about_us_content = Setting::getValueByKey('about_us', 'about_us', $headerValue);

        return view('landing-page.AboutUs', compact('about_us_content'));
    }


    public function refundPolicy(Request $request)
    {
        $headerValue = session()->get('locale', 'en');
        $refund_content = Setting::getValueByKey('refund_cancellation_policy', 'refund_cancellation_policy', $headerValue);
        $refund_content = $this->sanitizeContentInlineStyles($refund_content);

        return view('landing-page.RefundPolicy', compact('refund_content'));
    }

    public function helpSupport(Request $request)
    {
        $headerValue = session()->get('locale', 'en');
        $help_content = Setting::getValueByKey('help_support', 'help_support', $headerValue);
        $help_content = $this->sanitizeContentInlineStyles($help_content);

        return view('landing-page.helpSupport', compact('help_content'));
    }

    public function DataDeletion(Request $request)
    {
        $headerValue = session()->get('locale', 'en');
        $deletion_content = Setting::getValueByKey('data_deletion_request', 'data_deletion_request', $headerValue);
        $deletion_content = $this->sanitizeContentInlineStyles($deletion_content);

        return view('landing-page.dataDeletion', compact('deletion_content'));
    }

    private function sanitizeContentInlineStyles($content)
    {
        if (empty($content)) {
            return $content;
        }

        return preg_replace_callback('/style\s*=\s*(["\'])(.*?)\1/i', function ($matches) {
            $style = $matches[2];
            $style = preg_replace('/(?:^|;)\s*(?:color|background(?:-color)?)\s*:\s*[^;]+;?/i', ';', $style);
            $style = preg_replace('/;\s*;+/i', ';', $style);
            $style = trim($style, " ;\t\n\r\0\x0B");

            if ($style === '') {
                return '';
            }

            return 'style=' . $matches[1] . $style . $matches[1];
        }, $content);
    }

    public function bookServiceView(Request $request)
    {
        $service_id = $request->id;
        $shop_id = $request->shop_id;
        $service = Service::where('id', $service_id)->with('providers', 'category', 'serviceRating', 'serviceAddon')->first();
        $primary_locale = session()->get('locale', 'en');
        $service->name = $this->getTranslation($service->translations, $primary_locale, 'name', $service->name) ?? $service->name;
        $service->service_image = getSingleMedia($service, 'service_attachment', null);
        $service->category_name = $this->getTranslation(optional($service->category)->translations, $primary_locale, 'name', optional($service->category)->name ?? null) ?? optional($service->category)->name;
        $service->subcategory_name = $this->getTranslation(optional($service->subcategory)->translations, $primary_locale, 'name', optional($service->subcategory)->name ?? null) ?? optional($service->subcategory)->name;
        $service->provider_name = optional($service->providers)->display_name;
        $service->provider_image = getSingleMedia($service->providers, 'profile_image', null);
        $total_reviews = optional($service->serviceRating)->count();
        $total_rating = optional($service->serviceRating)->sum('rating');
        $service->total_reviews = $total_reviews;
        $service->total_rating = $total_reviews > 0 ? number_format($total_rating / $total_reviews, 2) : 0;
        $serviceconfig = Setting::getValueByKey('service-configurations', 'service-configurations');
        $global_advance_payment = isset($serviceconfig->global_advance_payment) ? $serviceconfig->global_advance_payment : 0;
        $globalAdvancePaymentPercentage = $global_advance_payment == 1 ? $serviceconfig->advance_paynment_percantage : 0;
        $service->advance_payment_amount = $service->is_enable_advance_payment == 1 ? ($service->advance_payment_amount === null ? 0 : (float) $service->advance_payment_amount) : (float) $globalAdvancePaymentPercentage;
        $service->is_enable_advance_payment = $service->is_enable_advance_payment == 1 ?  $service->is_enable_advance_payment : $global_advance_payment;



        // $coupons = Coupon::where('expire_date','>',date('Y-m-d H:i'))
        // ->where('status',1)
        // ->whereHas('serviceAdded',function($coupons) use($service_id){
        //     $coupons->where('service_id', $service_id );
        // })->get();


        // Get all valid coupons for the service without price filtering
        $coupons = Coupon::where('expire_date', '>', date('Y-m-d H:i'))
            ->where('status', 1)
            ->whereHas('serviceAdded', function ($q) use ($service_id) {
                $q->where('service_id', $service_id);
            })
            ->get();

        // Note: Price-based filtering is now handled dynamically in the frontend
        // based on the current quantity and total price.
        // The frontend will filter coupons based on (service->price * quantity)
        // when the user adjusts the quantity.


        $user_id = Auth::id();

        $taxes_data = ProviderTaxMapping::where('provider_id', $service->provider_id)
            ->with(['taxes' => function ($query) {
                $query->where('status', 1);
            }])
            ->orderBy('created_at', 'desc')->get();

        $transformedData = $taxes_data->map(function ($tax) {
            return [
                'id' => $tax->id,
                'provider_id' => $tax->provider_id,
                'title' => optional($tax->taxes)->title,
                'type' => optional($tax->taxes)->type,
                'value' => optional($tax->taxes)->value,
            ];
        });

        $availableserviceslot = null;

        if ($service->is_slot == 1) {

            $availableserviceslot = getServiceTimeSlot($service->provider_id);
        }

        $taxes = $transformedData;

        if ($request->package_id) {
            $service = ServicePackage::where('id', $request->package_id)->first();

            if ($service) {
                $primary_locale = session()->get('locale', 'en');
                $service->name = $this->getTranslation($service->translations, $primary_locale, 'name', $service->name) ?? $service->name;
                $service->description = $this->getTranslation($service->translations, $primary_locale, 'description', $service->description) ?? $service->description;
                $service->package_image = $service->getFirstMedia('package_attachment')->getUrl();
                $service->service_id = $service_id;
                $service->total_price = $service->getTotalPrice();
            }
        }

        $serviceaddon = null;
        if ($request->addons) {
            $addon_id = explode(',', $request->addons);
            $serviceaddons = ServiceAddon::whereIn('id', $addon_id)->get();

            $primary_locale = session()->get('locale', 'en');
            $serviceaddon = $serviceaddons->map(function ($addon) use ($primary_locale) {

                return [
                    'id' => $addon->id,
                    'name' => $this->getTranslation($addon->translations, $primary_locale, 'name', $addon->name) ?? $addon->name,
                    'description' => $this->getTranslation($addon->translations, $primary_locale, 'description', $addon->description) ?? $addon->description,
                    'price' => $addon->price,
                    'serviceaddon_image' => getSingleMedia($addon, 'serviceaddon_image', null),
                ];
            });
        }
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupdata = json_decode($sitesetup->value);
        $googlemapkey = $sitesetupdata->google_map_keys;

        // Timezone + format from settings (used for shop working hours)
        $targetTimezone = isset($sitesetupdata->time_zone) ? trim((string) $sitesetupdata->time_zone) : 'UTC';
        $timeFormat = $sitesetupdata->time_format ?? 'H:i';
        $user = auth()->user();
        $wallet = $user ? $user->wallet : null;
        $wallet_amount = $wallet ? $wallet->amount : 0;


        $shop_id = $request->shop_id;  // <-- use request value

        if (!$shop_id) {
            // Find nearest shop
            $lat  = $request->latitude ?? session('user_lat');
            $long = $request->longitude ?? session('user_lng');

            if ($lat && $long) {
                $nearest_shop = $service->shops
                    ->where('provider_id', $service->provider_id)
                    ->filter(fn($s) => $s->lat && $s->long)
                    ->map(function ($s) use ($lat, $long) {
                        $earthRadius = 6371;
                        $dLat = deg2rad($s->lat - $lat);
                        $dLon = deg2rad($s->long - $long);

                        $a = sin($dLat / 2) ** 2 +
                            cos(deg2rad($lat)) * cos(deg2rad($s->lat)) *
                            sin($dLon / 2) ** 2;

                        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                        $distance = $earthRadius * $c;

                        return (object)[
                            'id'        => $s->id,
                            'address'   => $s->address,
                            'start_time' => $s->shop_start_time,
                            'end_time'  => $s->shop_end_time,
                            'distance'  => round($distance, 2),
                        ];
                    })
                    ->filter(fn($s) => $s->distance <= 50)
                    ->sortBy('distance')
                    ->first();

                if ($nearest_shop) {
                    $shop_id = $nearest_shop->id;
                }
            }
        }

        $shop_list = $service->visit_type === 'on_shop'
            ? $service->shops()
            ->with(['city:id,name', 'state:id,name', 'country:id,name'])
            ->where('is_active', 1)
            ->where('provider_id', $service->provider_id)
            ->get()
            ->map(function ($shop) use ($targetTimezone, $timeFormat) {
                // Attach image
                $shop->shop_image = getSingleMedia($shop, 'shop_attachment', null);

                // Normalize working hours from stored UTC to site timezone
                $shop->shop_start_time = $shop->getRawOriginal('shop_start_time')
                    ? Carbon::parse($shop->getRawOriginal('shop_start_time'), 'UTC')
                    ->setTimezone($targetTimezone)
                    ->format($timeFormat)
                    : null;

                $shop->shop_end_time = $shop->getRawOriginal('shop_end_time')
                    ? Carbon::parse($shop->getRawOriginal('shop_end_time'), 'UTC')
                    ->setTimezone($targetTimezone)
                    ->format($timeFormat)
                    : null;

                return $shop;
            })
            : collect();


        // Fetch the wallet setting
        $otherSetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->where('key', 'OTHER_SETTING')->first();
        $wallet_enabled = $otherSetting && !empty(json_decode($otherSetting->value)->wallet);

        // Only set wallet amount if enabled
        $wallet_amount = 0;
        if ($wallet_enabled && $user && $user->wallet) {
            $wallet_amount = $user->wallet->amount;
        }


        $user_points = 0;

        if ($user_id) {
            $user_points = User::where('id', $user_id)->select('loyalty_points')->first()->loyalty_points;
        }

        if ($service->id && $service->service_id) {
            $redeem_points = LoyaltyRedeemRule::resolveRedeemRulesByPackage($service->id);
        } else {
            $redeem_points = LoyaltyRedeemRule::resolveRedeemRulesByService($service->id);
        }

        // Pass both to the view
        return view('landing-page.BookService', compact(
            'service',
            'shop_list',
            'shop_id',
            'coupons',
            'taxes',
            'user_id',
            'availableserviceslot',
            'serviceaddon',
            'googlemapkey',
            'wallet_amount',
            'wallet_enabled',
            'user_points',
            'redeem_points'
        ));
    }

    public function bookPostJobView(Request $request)
    {
        $user_id = Auth::id();
        $post_job_id = $request->id;
        $postJobController = app(PostJobRequestController::class);
        $apiRequest = new Request(['post_request_id' => $post_job_id]);
        $postJob = $postJobController->getPostRequestDetail($apiRequest);
        return view('landing-page.BookPostJob', compact('postJob', 'user_id'));
    }


    public function postJob()
    {
        $user_id = Auth::id();
        return view('landing-page.post-job-show', compact('user_id'));
    }

    public function bookingDetail(Request $request)
    {
        if (!auth()->check()) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $booking_id = $request->id;
        $findBooking = Booking::where('id', $booking_id)->first();
        if (is_null($findBooking)) {
            return redirect()->route('booking.list')->withErrors(trans('messages.booking_not_found'));;
        }
        $bookingController = app(BookingController::class);
        $apiRequest = new Request(['booking_id' => $booking_id]);
        $booking = $bookingController->getBookingDetail($apiRequest);

        $serviceType = $findBooking->service ? $findBooking->service->type : null;

        $user = auth()->user();
        $wallet = $user ? $user->wallet : null;
        $wallet_amount =  $wallet ? $wallet->amount : 0;
        $bookingData = json_decode($booking->content(), true);

        $serviceconfig = Setting::getValueByKey('service-configurations', 'service-configurations');
        $advancePaymentPercentage = isset($serviceconfig->advance_paynment_percantage) ? $serviceconfig->advance_paynment_percantage : 0;
        $global_advance_payment = isset($serviceconfig->global_advance_payment) ? $serviceconfig->global_advance_payment : 0;
        $bookingData['service']['is_enable_advance_payment'] = $bookingData['service']['type'] == 'fixed' ? ($bookingData['service']['is_enable_advance_payment'] == 1 ? $bookingData['service']['is_enable_advance_payment'] : $global_advance_payment) : 0;
        $advancepaymentamount = ($bookingData['booking_detail']['total_amount'] * $bookingData['service']['advance_payment_amount']) / 100;
        // $bookingData['cancellationcharges'] = isset($serviceconfig->cancellation_charge_amount) ?  (double)$serviceconfig->cancellation_charge_amount: 0;
        // $bookingData['cancellation_charge_amount'] = $findBooking->getCancellationCharges();
        if ($bookingData['booking_detail']['customer_id'] != auth()->user()->id) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $date_time = $sitesetup ? json_decode($sitesetup->value, true) : null;

        return view('landing-page.BookingDetail', compact('booking', 'wallet_amount', 'date_time', 'bookingData', 'advancepaymentamount', 'serviceType'));
    }

    public function postJobDetail(Request $request)
    {
        $post_job_id = $request->id;
        $postJobController = app(PostJobRequestController::class);
        $apiRequest = new Request(['post_request_id' => $post_job_id]);
        $postJob = $postJobController->getPostRequestDetail($apiRequest);
        return view('landing-page.post-job-detail', compact('postJob'));
    }

    public function favouriteServiceList(Request $request)
    {
        $user = auth()->user();
        $favouriteService = UserFavouriteService::where('user_id', $user->id)->get();

        $isEmpty = $favouriteService->isEmpty();

        return view('landing-page.FavouriteService', compact('isEmpty'));
    }

    public function servicePackageList(Request $request)
    {
        $service_id = $request->id;
        $serviceController = app(ServiceController::class);
        $apiRequest = new Request(['service_id' => $service_id]);
        $service = $serviceController->getServiceDetail($apiRequest);
        $serviceData = json_decode($service->content(), true);
        return view('landing-page.ServicePackages', compact('service', 'serviceData'));
    }

    public function ratingList(Request $request)
    {

        if ($request->handyman_id) {
            $query = HandymanRating::query()->orderBy('created_at', 'desc');
        } else {
            $query = BookingRating::query()->orderBy('created_at', 'desc');
        }

        $review_count = 0;

        if ($request->provider_id) {
            $id = $request->provider_id;
            $type = "provider-rating";

            $query = $query->whereHas('service', function ($q) use ($id) {
                $q->where('provider_id', $id);
            });

            $review_count = count($query->get());
        } else if ($request->handyman_id) {
            $id = $request->handyman_id;
            $type = 'handyman-rating';

            $query = $query->where('handyman_id', $id);

            $review_count = count($query->get());
        } else if ($request->service_id) {
            $id = $request->service_id;
            $type = "service-rating";
            $query = $query->where('service_id', $id);
            $review_count = count($query->get());
        }

        return view('landing-page.RatingAll', compact('id', 'type', 'review_count'));
    }

    public function categoryDatatable(Datatables $datatable, Request $request)
    {
        $query = Category::query();

        $query = $query->where('status', 1);

        $filter = $request->filter;
        $primary_locale = session()->get('locale', 'en');
        if (isset($filter['search'])) {
            //$query->where('name', 'LIKE', '%'.$filter['search'].'%');
            if ($primary_locale !== 'en') {
                $query->whereHas('translations', function ($query) use ($filter, $primary_locale) {
                    $query->where('locale', $primary_locale)
                        ->where('attribute', 'name')
                        ->where('value', 'LIKE', '%' . $filter['search'] . '%');
                });
            } else {
                $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
            }
        }
        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) use ($primary_locale) {
                $data->name = $this->getTranslation($data->translations, $primary_locale, 'name', $data->name) ?? $data->name;
                $data->description = $this->getTranslation($data->translations, $primary_locale, 'description', $data->description) ?? $data->description;
                return view('category.datatable-card', compact('data', 'primary_locale'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            });


        return $datatable->rawColumns(['name'])
            ->toJson();
    }

    public function subCategoryDatatable(Datatables $datatable, Request $request)
    {
        $query = SubCategory::query();
        $query = $query->where('status', 1);
        $query = $query->where('category_id', $request->category_id);

        $primary_locale = session()->get('locale', 'en');
        $filter = $request->filter ?? [];
        if (!empty($filter['search'])) {
            if ($primary_locale !== 'en') {
                $query->whereHas('translations', function ($q) use ($filter, $primary_locale) {
                    $q->where('locale', $primary_locale)
                        ->where('attribute', 'name')
                        ->where('value', 'LIKE', '%' . $filter['search'] . '%');
                });
            } else {
                $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
            }
        }

        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) use ($primary_locale) {
                $data->name = $this->getTranslation($data->translations, $primary_locale, 'name', $data->name) ?? $data->name;
                $data->description = $this->getTranslation($data->translations, $primary_locale, 'description', $data->description) ?? $data->description;
                return view('subcategory.datatable-card', compact('data'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km')
    {
        $earthRadius = $unit === 'km' ? 6371 : 3959; // Radius in km or miles

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    public function serviceDatatable(Datatables $datatable, Request $request)
    {
        $query = Service::where('service_type', 'service')
            ->where('status', 1)
            ->where('service_request_status', 'approve');

        $primary_locale = session()->get('locale', 'en');

        if (session()->has('user_lat') && session()->has('user_lng')) {
            $lat = $request->latitude ?? session('user_lat');
            $lng = $request->longitude ?? session('user_lng');
        }
      
        if (isset($lat) && !empty($lat) && isset($lng) && !empty($lng)) {

            $serviceZone = ServiceZone::all();

            if (count($serviceZone) > 0) {

                try {

                    $matchingZoneIds = $this->getMatchingZonesByLatLng($lat, $lng);

                    $query->whereHas('serviceZoneMapping', function ($query) use ($matchingZoneIds) {
                        $query->whereIn('zone_id', $matchingZoneIds);
                    });
                } catch (\Exception $e) {
                    $query = $query;
                }
            } else {

                $get_distance = getSettingKeyValue('site-setup', 'radious') ?? 50;
                $get_unit = getSettingKeyValue('site-setup', 'distance_type') ?? 'km';

                $locations = $query->locationService($lat, $lng, $get_distance, $get_unit);
                $service_in_location = ProviderServiceAddressMapping::whereIn('provider_address_id', $locations)->get()->pluck('service_id');
                $query->with('providerServiceAddress')->whereIn('id', $service_in_location);
            }
        }

        if ($request->type == 'shop-service') {
            $query->whereHas('shops', function ($q) use ($request) {
                $q->where('shops.id', $request->id);
            });
        }


        if ($request->type == 'provider-service') {
            $query->where('provider_id', $request->id);
        }
        if ($request->type == 'subcategory-service') {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('subcategory_id', $request->id);
            });
        }
        if ($request->type == 'category-service') {
            $query->where('category_id', $request->id);
        }
        if ($request->has('zone_id') && !empty($request->zone_id)) {
            $query->whereHas('zones', function ($q) use ($request) {
                $q->where('service_zones.id', $request->zone_id);
            });
        }

        if (default_earning_type() === 'subscription') {
            $query->whereHas('providers', function ($a) use ($request) {
                $a->where('status', 1)->where('is_subscribe', 1);
            });
        }

        $filter = $request->filter;

        if (isset($filter['search'])) {
            // $query->where('name', 'LIKE', '%'.$filter['search'].'%');
            if ($primary_locale !== 'en') {
                $query->whereHas('translations', function ($query) use ($filter, $primary_locale) {
                    $query->where('locale', $primary_locale)
                        ->where('attribute', 'name')
                        ->where('value', 'LIKE', '%' . $filter['search'] . '%');
                });
            } else {
                $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
            }
        }
        if (isset($filter['selectedCategory'])) {
            $query->where('category_id', $filter['selectedCategory']);
        }
        if (isset($filter['selectedProvider'])) {
            $query->where('provider_id', $filter['selectedProvider']);
        }
        if (isset($filter['selectedPriceRange'])) {
            $priceRange = explode('-', $filter['selectedPriceRange']);

            if (count($priceRange) === 2) {
                $minPrice = $priceRange[0];
                $maxPrice = $priceRange[1];
                $query->whereBetween('price', [$minPrice, $maxPrice]);
            }
        }
        if (isset($filter['selectedSortOption'])) {
            if ($filter['selectedSortOption'] == "best_selling") {
                $bestSellingServiceIds = Booking::select('service_id', \DB::raw('COUNT(service_id) as service_count'))
                    ->groupBy('service_id')
                    ->orderByDesc('service_count')
                    ->pluck('service_id')
                    ->toArray();

                if (!empty($bestSellingServiceIds)) {
                    $query->whereIn('id', $bestSellingServiceIds)
                        ->orderByRaw("FIELD(id, " . implode(',', $bestSellingServiceIds) . ")");
                }
            }
            if ($filter['selectedSortOption'] == "top_rated") {
                $topRatedServiceIds = BookingRating::select(
                    'service_id',
                    \DB::raw('COALESCE(AVG(rating), 0) as avg_rating'),
                    \DB::raw('COUNT(rating) as total_reviews')
                )
                    ->groupBy('service_id')
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('total_reviews')
                    ->pluck('service_id')
                    ->toArray();

                if (!empty($topRatedServiceIds)) {

                    $allServiceIds = $query->pluck('id')->toArray();
                    $otherServiceIds = array_diff($allServiceIds, $topRatedServiceIds);
                    $orderedServiceIds = array_merge($topRatedServiceIds, $otherServiceIds);
                    $orderedServiceIds = array_map('intval', $orderedServiceIds);
                    $query->whereIn('id', $orderedServiceIds)
                        ->orderByRaw("FIELD(id, " . implode(',', $orderedServiceIds) . ")");
                }
            }

            if ($filter['selectedSortOption'] == "newest") {
                $query->orderBy('created_at', 'desc');
            }
        }


        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) use ($primary_locale) {
                $data->name = $this->getTranslation($data->translations, $primary_locale, 'name', $data->name) ?? $data->name;
                $totalReviews = $data->id ? BookingRating::where('service_id', $data->id)->count() : 0;
                $totalRating = $data->serviceRating ? (float) number_format(max($data->serviceRating->avg('rating'), 0), 2) : 0;
                if (!empty(auth()->user())) {
                    $favouriteService = $data->getUserFavouriteService()->where('user_id', auth()->user()->id)->get();
                } else {
                    $favouriteService = collect();
                }
                return view('service.datatable-card', compact('data', 'totalReviews', 'totalRating', 'favouriteService'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            });
            
        return $datatable->rawColumns(['name'])
            ->toJson();
    }

    public function blogDatatable(Datatables $datatable, Request $request)
    {
        $query = Blog::query();
        $query = $query->where('status', 1);
        $filter = $request->filter;
        if (isset($filter['search'])) {
            $query->where('title', 'LIKE', '%' . $filter['search'] . '%');
        }

        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                return view('blog.datatable-card', compact('data'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }

    public function providerDatatable(Datatables $datatable, Request $request)
    {
        $query = User::with('getServiceRating')
            ->where('user_type', 'provider')
            ->where('status', 1);


        if (session()->has('user_lat') && session()->has('user_lng')) {
            $lat = $request->latitude ?? session('user_lat');
            $lng = $request->longitude ?? session('user_lng');
        }

        if (isset($lat) && !empty($lat) && isset($lng) && !empty($lng)) {

            $serviceZone = ServiceZone::all();

            if (count($serviceZone) > 0) {

                try {

                    $matchingZoneIds = $this->getMatchingZonesByLatLng($lat, $lng);

                    $query->whereHas('providerZones', function ($query) use ($matchingZoneIds) {
                        $query->whereIn('zone_id', $matchingZoneIds);
                    });
                } catch (\Exception $e) {
                    $query = $query;
                }
            }
        }


        // Subscription check if needed
        if (default_earning_type() === 'subscription') {
            $query->where('status', 1)->where('is_subscribe', 1);
        }

        $filter = $request->filter;
        if (isset($filter['search'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('first_name', 'LIKE', '%' . $filter['search'] . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $filter['search'] . '%');
            });
        }

        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                $providers_service_rating = 0;
                if ($data->relationLoaded('getServiceRating') && $data->getServiceRating->count()) {
                    $providers_service_rating = round($data->getServiceRating->avg('rating'), 1);
                }

                return view('provider.datatable-card', compact('data', 'providers_service_rating'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            });

        return $datatable->rawColumns(['name'])->toJson();
    }

    public function shopDatatable(Datatables $datatable, Request $request)
    {
        $locale = ($request->filter ?? [])['locale'] ?? session()->get('locale', 'en');
        if (in_array($locale, config('app.available_locales', ['en', 'ar', 'de', 'es', 'fr', 'hi', 'it', 'nl']))) {
            \App::setLocale($locale);
        }
        $latitude = $request->latitude ?? session('user_lat');
        $longitude = $request->longitude ?? session('user_lng');

        $lat = !empty($latitude) ? $latitude : null;
        $long = !empty($longitude) ? $longitude : null;

        $query = Shop::where('is_active', 1)->nearByShop($lat, $long);

        if (default_earning_type() === 'subscription') {
            $query->whereHas('provider', function ($a) {
                $a->where('status', 1)->where('is_subscribe', 1);
            });
        }


        if ($request->type === 'on_shop_service' && !empty($request->id)) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('services.id', $request->id);
            });
        }

        if ($request->type === 'provider_shops' && !empty($request->id)) {
            $query->where('provider_id', $request->id);
        }

        $filter = $request->filter;

        if (!empty($filter['provider'])) {
            $query->where('provider_id', $filter['provider']);
        }

        if (!empty($filter['search'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('shop_name', 'LIKE', '%' . $filter['search'] . '%')
                    ->orWhereHas('provider', function ($q) use ($filter) {
                        $q->where('first_name', 'LIKE', '%' . $filter['search'] . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $filter['search'] . '%');
                    });
            });
        }
        return $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                return view('shop.datatable-card', compact('data'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            })
            ->rawColumns(['name'])
            ->toJson();
    }


    public function bookingDatatable(Datatables $datatable, Request $request)
    {
        $query = Booking::query();
        $query = $query->where('customer_id', auth()->user()->id);
        $primary_locale = session()->get('locale', 'en');
        $filter = $request->filter;
        if (isset($filter['search'])) {
            $searchTerm = $filter['search'];

            // Check if search term is numeric (likely a booking ID)
            if (is_numeric($searchTerm)) {
                $query->where(function ($q) use ($searchTerm, $primary_locale) {
                    // Search by booking ID
                    $q->where('id', $searchTerm)
                        // Also search by service name as fallback
                        ->orWhereHas('service', function ($serviceQuery) use ($searchTerm, $primary_locale) {
                            if ($primary_locale !== 'en') {
                                $serviceQuery->where(function ($sq) use ($searchTerm, $primary_locale) {
                                    $sq->whereHas('translations', function ($tq) use ($searchTerm, $primary_locale) {
                                        $tq->where('locale', $primary_locale)
                                            ->where('value', 'LIKE', '%' . $searchTerm . '%');
                                    })
                                        ->orWhere('name', 'LIKE', '%' . $searchTerm . '%');
                                });
                            } else {
                                $serviceQuery->where('name', 'LIKE', '%' . $searchTerm . '%');
                            }
                        });
                });
            } else {
                // Search by service name only (non-numeric search)
                $query->whereHas('service', function ($q) use ($searchTerm, $primary_locale) {
                    if ($primary_locale !== 'en') {
                        $q->where(function ($q) use ($searchTerm, $primary_locale) {
                            $q->whereHas('translations', function ($q) use ($searchTerm, $primary_locale) {
                                $q->where('locale', $primary_locale)
                                    ->where('value', 'LIKE', '%' . $searchTerm . '%');
                            })
                                ->orWhere('name', 'LIKE', '%' . $searchTerm . '%');
                        });
                    } else {
                        $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                    }
                });
            }
        }
        if (isset($filter['booking_date_range'])) {

            $startDate = explode(' to ', $filter['booking_date_range'])[0];

            $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
            $startDate = \Carbon\Carbon::parse($startDate);
            $startDate = $startDate->format('Y-m-d');

            $endDate = explode(' to ', $filter['booking_date_range'])[1];
            $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');
            $endDate = \Carbon\Carbon::parse($endDate);
            $endDate = $endDate->format('Y-m-d');

            $query->whereDate('date', '>=', $startDate);
            $query->whereDate('date', '<=', $endDate);
        }

        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        $query->orderByDesc('id');
        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) use ($primary_locale) {
                $service = optional($data->service);
                $servicename = $this->getTranslation($data->service->translations, $primary_locale, 'name', optional($data->service)->name) ?? optional($data->service)->name;
                $servicepackagename = null;
                if (isset($data->bookingPackage)) {
                    $servicepackagename = $this->getTranslation($data->bookingPackage->package->translations, $primary_locale, 'name', optional(optional($data->bookingPackage)->package)->name) ?? optional($data->bookingPackage)->name;
                }

                $serviceimage = getSingleMedia($service, 'service_attachment', null);
                $total_rating = (float) number_format(max(optional($data->service)->serviceRating->avg('rating'), 0), 2);
                $advancepaid = 0;

                if ($data->status == 'cancelled') {
                    $advancepaid = $data->advance_paid_amount == null ? 0 : (float) $data->advance_paid_amount;
                }
                $net_refund = $advancepaid > 0 ? max(0, $advancepaid - (float) ($data->cancellation_charge_amount ?? 0)) : 0;
                $refund_amount = $net_refund;
                $refund_status = $net_refund > 0 ? 'completed' : null;
                $payment = $data->payment()->orderBy('id', 'desc')->first();
                $is_at_shop = $service->visit_type == 'on_shop' ? true : false;
                return view('booking.datatable-card', compact('servicepackagename', 'data', 'servicename', 'payment', 'total_rating', 'serviceimage', 'refund_amount', 'refund_status', 'is_at_shop'));
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }

    public function postJobDatatable(Datatables $datatable, Request $request)
    {
        $query = PostJobRequest::myPostJob()->whereIn('status', ['requested', 'accepted', 'assigned']);
        $filter = $request->filter;
        if (isset($filter['search'])) {
            $searchTerm = $filter['search'];
            $query->where(function ($query) use ($searchTerm) {
                $query->where('status', 'LIKE', '%' . $searchTerm . '%');
                $query->orWhereHas('postServiceMapping', function ($serviceQuery) use ($searchTerm) {
                    $serviceQuery->where('service_id', '!=', null)
                        ->whereExists(function ($subServiceQuery) use ($searchTerm) {
                            $subServiceQuery->selectRaw(1)
                                ->from('services')
                                ->whereRaw('services.id = post_job_service_mappings.service_id')
                                ->where('name', 'LIKE', '%' . $searchTerm . '%');
                        });
                });
            });
        }
        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                $services = optional($data->postServiceMapping);
                $serviceImages = $services->map(function ($service) {
                    $serviceId = $service->service_id;
                    $image = optional(Service::find($serviceId)->getFirstMedia('service_attachment'))->getUrl();
                    return $image;
                });
                return view('postrequest.datatable-card', compact('data', 'serviceImages'));
            })
            ->order(function ($query) {
                $query->orderBy('id', 'desc');
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }


    public function favouriteServiceDatatable(Datatables $datatable, Request $request)
    {
        $user = auth()->user();

        $favouriteServiceIds = UserFavouriteService::where('user_id', $user->id)->pluck('service_id')->toArray();

        $query = Service::whereIn('id', $favouriteServiceIds)
            ->where('service_request_status', 'approve')
            ->where('status', 1);

        $primary_locale = session()->get('locale', 'en');
        $filter = $request->filter;
        if (isset($filter['search'])) {
            if ($primary_locale !== 'en') {
                $query->where(function ($query) use ($filter, $primary_locale) {
                    $query->whereHas('translations', function ($query) use ($filter, $primary_locale) {
                        // Search in the translations table based on the primary_locale
                        $query->where('locale', $primary_locale)
                            ->where('value', 'LIKE', '%' . $filter['search'] . '%');
                    })
                        ->orWhere('name', 'LIKE', '%' . $filter['search'] . '%'); // Fallback to 'name' field if no translation is found
                });
            } else {
                $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
            }
            // $query->where('name', 'LIKE', '%'.$filter['search'].'%');
        }

        if (default_earning_type() === 'subscription') {
            $query->whereHas('providers', function ($a) {
                $a->where('status', 1)->where('is_subscribe', 1);
            });
        }
        if (isset($filter['selectedCategory'])) {
            $query->where('category_id', $filter['selectedCategory']);
        }
        if (isset($filter['selectedProvider'])) {
            $query->where('provider_id', $filter['selectedProvider']);
        }
        if (isset($filter['selectedPriceRange'])) {
            $priceRange = explode('-', $filter['selectedPriceRange']);

            if (count($priceRange) === 2) {
                $minPrice = $priceRange[0];
                $maxPrice = $priceRange[1];
                $query->whereBetween('price', [$minPrice, $maxPrice]);
            }
        }
        if (isset($filter['selectedSortOption'])) {
            if ($filter['selectedSortOption'] == "best_selling") {
                $bestSellingServiceIds = Booking::select('service_id', \DB::raw('COUNT(service_id) as service_count'))
                    ->groupBy('service_id')
                    ->orderByDesc('service_count')
                    ->pluck('service_id')
                    ->toArray();
                if (!empty($bestSellingServiceIds)) {
                    $query->whereIn('id', $bestSellingServiceIds)
                        ->orderByRaw("FIELD(id, " . implode(',', $bestSellingServiceIds) . ")");
                } else {
                    // Handle the case where there are no top-rated services
                    $query->whereRaw('1 = 0'); // This will always return an empty result
                }
            }
            if ($filter['selectedSortOption'] == "top_rated") {
                $topRatedServiceIds = BookingRating::select('service_id', \DB::raw('COALESCE(AVG(rating), 0) as avg_rating'))
                    ->groupBy('service_id')
                    ->orderByDesc('avg_rating')
                    ->pluck('service_id')
                    ->toArray();

                if (!empty($topRatedServiceIds)) {
                    $query->whereIn('id', $topRatedServiceIds)
                        ->orderByRaw("FIELD(id, " . implode(',', $topRatedServiceIds) . ")");
                } else {
                    // Handle the case where there are no top-rated services
                    $query->whereRaw('1 = 0'); // This will always return an empty result
                }
            }
            if ($filter['selectedSortOption'] == "newest") {
                $query->orderBy('created_at', 'desc');
            }
        }


        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) use ($primary_locale) {
                $totalReviews = BookingRating::where('service_id', $data->id)->count();
                $totalRating = count($data->serviceRating) > 0
                    ? (float)number_format(max($data->serviceRating->avg('rating'), 0), 2)
                    : 0;

                if (!empty(auth()->user())) {
                    $favouriteService = $data->getUserFavouriteService()->where('user_id', auth()->user()->id)->get();
                } else {
                    $favouriteService = collect();
                }

                $favouriteServicename = $this->getTranslation($data->translations, $primary_locale, 'name', $data->name) ?? $data->name;

                return view('service.datatable-card', compact('data', 'totalReviews', 'totalRating', 'favouriteService', 'favouriteServicename'));
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }
    public function ratingDatatable(Datatables $datatable, Request $request)
    {

        $id = $request->id;
        if ($request->type == 'handyman-rating') {
            $query = HandymanRating::query()->orderBy('created_at', 'desc');
            $query = $query->where('handyman_id', $id);
        } else {
            $query = BookingRating::query()->orderBy('created_at', 'desc');
        }
        if ($request->type == 'provider-rating') {
            $query = $query->whereHas('service', function ($q) use ($id) {
                $q->where('provider_id', $id);
            });
        } elseif ($request->type == 'service-rating') {
            $query = $query->where('service_id', $id);
        }

        $filter = $request->filter;
        if (isset($filter['search'])) {
            $query->WhereHas('customer', function ($q) use ($filter) {
                $q->where('display_name', 'LIKE', '%' . $filter['search'] . '%');
            });
        }

        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                return view('ratingreview.datatable-card', compact('data'));
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }
    public function userSubscribe(Request $request)
    {
        $emailData['email'] = $request->email;
        $emailData['title'] = env('APP_NAME');
        $emailData['body'] = 'Thank you for subscribe us.';
        try {
            \Mail::send('customer.subscribe_email', $emailData, function ($message) use ($emailData) {
                $message->to($emailData['email'])
                    ->subject($emailData['title']);
            });

            $messagedata = __('landingpage.subscribe_msg');
            return comman_message_response($messagedata);
        } catch (\Throwable $e) {
            \Log::error('Subscribe email failed: ' . $e->getMessage());
            $messagedata = __('messages.something_wrong');
            return comman_message_response($messagedata);
        }
    }
    public function helpdeskList(Request $request)
    {
        $query = HelpDesk::query();
        $data = $query->where('employee_id', auth()->user()->id)->get();
        $addpermission = auth()->user()->can('helpdesk add') ? true : false;
        return view('landing-page.helpdesk', compact('data', 'addpermission'));
    }
    public function helpdeskDatatable(Datatables $datatable, Request $request)
    {
        $query = HelpDesk::query();
        $query = $query->where('employee_id', auth()->user()->id);
        $filter = $request->filter;


        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }



        $query->orderByDesc('id');

        $datatable = $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                $service = optional($data->service);
                $serviceimage = getSingleMedia($service, 'service_attachment', null);
                $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                return view('helpdesk.datatable-card', compact('data', 'datetime'));
            });

        return $datatable->rawColumns(['name'])
            ->toJson();
    }
    public function helpdeskDetail(Request $request)
    {

        $helpdesk_id = $request->id;
        $findHelpdesk = HelpDesk::with('helpdeskactivity')->where('id', $helpdesk_id)->first();
        if (empty($findHelpdesk)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.helpdesk')]);
            return redirect(route('helpdesk.list'))->withError($msg);
        }
        if ($findHelpdesk['employee_id'] != auth()->user()->id) {
            return redirect(route('frontend.index'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $helpdeskController = app(HelpDeskController::class);
        $apiRequest = new Request(['id' => $helpdesk_id]);
        $helpdesk = $helpdeskController->getHelpDeskDetail($apiRequest);

        $helpdeskData = json_decode($helpdesk->content(), true);
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $date_time = $sitesetup ? json_decode($sitesetup->value, true) : null;
        return view('landing-page.HelpdeskDetail', compact('findHelpdesk', 'date_time', 'helpdeskData'));
    }

    public function setLocation(Request $request)
    {

        session([
            'user_lat' => $request->lat,
            'user_lng' => $request->lng,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session cleared and cache flushed successfully',
            'data' => [
                'user_lat' => session('user_lat'), // should return ''
                'user_lng' => session('user_lng'),
            ],
        ]);
    }



    private function getNearbyZoneIds($providerId, $lat, $lng, $limit, $unit): array
    {
        $zoneMappings = ProviderZoneMapping::with('zone')
            ->where('provider_id', $providerId)
            ->get();

        $zoneIds = [];

        foreach ($zoneMappings as $mapping) {
            $zone = $mapping->zone;

            if (!$zone || !$zone->coordinates) continue;

            $coordinates = $zone->coordinates;
            if (is_string($coordinates)) {
                $decoded = json_decode($coordinates, true);
                if (is_string($decoded)) {
                    $coordinates = json_decode($decoded, true); // double decode
                } else {
                    $coordinates = $decoded;
                }
            }

            if (is_array($coordinates)) {
                foreach ($coordinates as $coord) {
                    if (isset($coord['lat'], $coord['lng'])) {
                        $distance = $this->calculateDistance($lat, $lng, $coord['lat'], $coord['lng'], $unit);
                        // if ($distance <= $limit) {
                        $zoneIds[] = $zone->id;
                        break; // only one match needed
                        // }
                    }
                }
            }
        }

        return $zoneIds;
    }

    public function loyaltyPoints(Request $request)
    {
        $user = null;

        if (auth()->check() && auth()->user()->hasRole('user')) {
            $user = User::find(auth()->user()->id);
        }

        if (is_null($user)) {
            return redirect()->route('user.login');
        }

        $referralRule = LoyaltyReferralRule::where('status', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $referrer_points = $referralRule ? $referralRule->referrer_points : null;
        $referred_user_points = $referralRule ? $referralRule->referred_user_points : null;

        return view('landing-page.loyalty-points', compact('user', 'referrer_points', 'referred_user_points'));
    }

    public function loyaltyHistoryList(Request $request)
    {
        $activity = LoyaltyPointActivity::where('user_id', $request->user_id)->orderBy('id', 'desc');

        if ($request->filled('filter')) {
            $filterType = $request->input('filter.type');
            switch ($filterType) {

                case 'last_week':
                    $activity->where('created_at', '>=', now()->subWeek());
                    break;

                case 'last_month':
                    $start = now()->subMonth()->startOfMonth();
                    $end   = now()->subMonth()->endOfMonth();
                    $activity->whereBetween('created_at', [$start, $end]);
                    break;

                case 'last_year':
                    $start = now()->subYear()->startOfYear();
                    $end   = now()->subYear()->endOfYear();
                    $activity->whereBetween('created_at', [$start, $end]);
                    break;

                default:
                    // no filter
                    break;
            }
        }

        // Get date format from settings
        $site_setup = optional(json_decode(
            Setting::where('type', 'site-setup')->where('key', 'site-setup')->value('value') ?? '{}'
        ));

        $date_format = $site_setup->date_format ?? "F j, Y";
        $time_format = $site_setup->time_format ?? "g:i A";
        $time_zone = $site_setup->time_zone ?? "UTC";

        return DataTables::of($activity)
            ->addColumn('loyalty_type', function ($row) {
                $booking_id = $row->type === "referral" ? '' : $row->related_id;
                $type_key = 'messages.' . $row->source;
                $type_text = __($type_key);

                if ($booking_id) {
                    $url = route('booking.detail', $booking_id);
                    $bookingLink = ' <a href="' . $url . '" class="text-primary">#' . e($booking_id) . '</a>';
                } else {
                    $bookingLink = '';
                }

                return '<span>' . e($type_text) . $bookingLink . '</span>';
            })
            ->addColumn('loyalty_points', function ($row) {
                $points = $row->points ?? 0;
                if ($row->earn_type === 'credit') {
                    return '<span class="text-success">+' . $points . '</span>';
                } else {
                    return '<span class="text-danger">-' . $points . '</span>';
                }
            })
            ->addColumn('date_and_time', function ($row) use ($date_format, $time_format, $time_zone) {
                try {
                    $date = \Carbon\Carbon::parse($row->created_at, 'UTC')->setTimezone($time_zone);
                    return $date->format($date_format . ', ' . $time_format);
                } catch (\Exception $e) {
                    \Log::error('Date formatting error in frontend loyalty history: ' . $e->getMessage(), [
                        'created_at' => $row->created_at,
                        'date_format' => $date_format,
                        'time_format' => $time_format,
                        'time_zone' => $time_zone
                    ]);
                    return $row->created_at;
                }
            })
            ->addColumn('status', function ($row) {
                if ($row->earn_type === 'credit') {
                    return '<span class="badge bg-success-subtle text-success w-50 p-3">' . __('messages.credit') . '</span>';
                } else {
                    return '<span class="badge bg-danger-subtle text-danger w-50 p-3">' . __('messages.debit') . '</span>';
                }
            })
            ->rawColumns(['status', 'loyalty_points', 'loyalty_type'])
            ->toJson();
    }
}
