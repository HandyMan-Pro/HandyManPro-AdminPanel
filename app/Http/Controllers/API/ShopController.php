<?php

namespace App\Http\Controllers\API;

use App\Models\Shop;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShopRequest;
use App\Http\Requests\ShopHourStoreRequest;
use App\Http\Resources\API\ShopResource;
use App\Models\ShopHour;
use App\Http\Resources\API\ShopDetailResource;
use App\Models\Setting;
use Carbon\Carbon;
use App\Traits\NotificationTrait;
use App\Traits\ZoneTrait;
use App\Http\Resources\API\ShopHourResource;

class ShopController extends Controller
{
    use NotificationTrait, ZoneTrait;

    /**
     * Normalize multipart `translations` payload.
     * Service API expects `translations` as a JSON string; same here.
     *
     * Input example (non-English only):
     *  {"ar":{"name":"متجري"},"de":{"name":"Mein Laden"}}
     *
     * Output (for TranslationTrait::saveTranslations, non-English only):
     *  ["ar" => ["shop_name" => "متجري"], "de" => ["shop_name" => "Mein Laden"]]
     *
     * @return array<string, array<string, string>>|null null = key absent (do not call saveTranslations)
     */
    protected function shopTranslationsPayloadForSave(Request $request): ?array
    {
        if (!$request->has('translations')) {
            return null;
        }

        $raw = $request->input('translations');

        if ($raw === null || $raw === '') {
            return [];
        }

        // multipart may send translations as JSON string
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $locale => $fields) {
            if (!is_string($locale)) {
                continue;
            }

            $locale = strtolower(trim($locale));
            if ($locale === 'en') {
                continue;
            }

            if (!is_array($fields)) {
                continue;
            }

            $name = $fields['shop_name'] ?? $fields['name'] ?? null;
            if ($name !== null && trim((string) $name) !== '') {
                // Shop translations are stored under attribute `shop_name`
                $normalized[$locale] = ['shop_name' => $name];
            }
        }

        return $normalized;
    }


    public function getShopList(Request $request)
    {

        $perPage = min($request->input('per_page', 10), 100);
        $page = $request->input('page', 1);
        
        $lat = $request->latitude ?? null;
        $lng = $request->longitude ?? null;

        $shops = Shop::with(['city', 'state', 'country', 'provider', 'translations']);
        if (!$request->has('provider_id')) {
            if (default_earning_type() === 'subscription') {
                $shops = $shops->whereHas('provider', function ($query) {
                    $query->where('status', 1)->where('is_subscribe', 1);
                });
            }
        }


        if ($request->filled('provider_id')) {
            $providerIds = explode(',', $request->provider_id);
            $shops->whereIn('provider_id', $providerIds);
        }

        if ($request->filled('service_id')) {
            $serviceIds = explode(',', $request->service_id);
            $shops->whereHas('services', function ($query) use ($serviceIds) {
                $query->whereIn('services.id', $serviceIds);
            });
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = trim($request->search);
            $shops = $shops->where(function($query) use ($search) {
                $query->where('shop_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('translations', function($q) use ($search) {
                        $q->where('attribute', 'shop_name')
                          ->whereRaw('LOWER(value) LIKE ?', ['%' . strtolower($search) . '%']);
                    });
            });
        }

        if ($request->has('country_id') && !empty($request->country_id)) {

            $shops = $shops->where('country_id', $request->country_id);
        }
        
        // Zone-based filtering when location is enabled
        // Check if location_enabled parameter is explicitly set to true/1
        $locationEnabled = $request->has('location_enabled') && in_array($request->location_enabled, [true, 'true', 1, '1']);
        
        if ($locationEnabled && $lat && !empty($lat) && $lng && !empty($lng)) {
            $serviceZone = \App\Models\ServiceZone::all();

            if (count($serviceZone) > 0) {
                try {
                    // Get matching zones based on user's lat/lng
                    $matchingZoneIds = $this->getMatchingZonesByLatLng($lat, $lng);

                    // Filter shops by zone through their provider's zones
                    $shops->whereHas('provider.providerZones', function ($query) use ($matchingZoneIds) {
                        $query->whereIn('zone_id', $matchingZoneIds);
                    });
                } catch (\Exception $e) {
                    // If zone matching fails, don't filter
                    \Log::error('Shop zone filtering failed: ' . $e->getMessage());
                }
            }
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $shops->count();
            }
        }

        $shops = $shops->orderBy('created_at', 'desc')->paginate($perPage);

        $items = ShopResource::collection($shops);

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

    public function getShopDetail(Request $request, $id)
    {
        $shop = Shop::withTrashed()->with([
            'city', 
            'state', 
            'country', 
            'provider', 
            'shopHours', 
            'translations',
            'services'
        ])->find($id);

        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }
        $shop->shopHours = $shop->shopHours()->orderByRaw("FIELD(day, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")->get();
        return response()->json([
            'status' => true,
            'message' => 'Shop detail retrieved successfully.',
            'shop' => new ShopDetailResource($shop)
        ], 200);
    }

    public function shopCreate(ShopRequest $request)
    {
        $data = $request->except(['service_ids', 'shop_attachment']);
        if (isset($data['is_active'])) {
            if ($data['is_active'] === 'false' || $data['is_active'] === false || $data['is_active'] === '0') {
                $data['is_active'] = 0;
            } elseif ($data['is_active'] === 'true' || $data['is_active'] === true || $data['is_active'] === '1') {
                $data['is_active'] = 1;
            }
        }
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode($sitesetup->value);
        $timezone = $sitesetupValue->time_zone ?? 'UTC';

        // $data['shop_start_time'] = Carbon::parse($data['shop_start_time'], $timezone)
        //     ->setTimezone('UTC')
        //     ->format('H:i:s');

        // $data['shop_end_time'] = Carbon::parse($data['shop_end_time'], $timezone)
        //     ->setTimezone('UTC')
        //     ->format('H:i:s');


        $shop = Shop::create($data);

        $language_option = sitesetupSession('get')->language_option ?? ['ar', 'nl', 'en', 'fr', 'de', 'hi', 'it'];
        $translationsPayload = $this->shopTranslationsPayloadForSave($request);
        if ($translationsPayload !== null) {
            $shop->saveTranslations(['translations' => $translationsPayload], ['shop_name'], $language_option, 'en');
        }

        // Sync selected services
        if ($request->filled('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        }

        if ($request->is('api/*')) {
            // Handle API image uploads
            $file = [];
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachmentKey = "shop_attachment_" . $i;
                    if ($request->$attachmentKey != null) {
                        $file[] = $request->$attachmentKey;
                    }
                }

                if (!empty($file)) {
                    storeMediaFile($shop, $file, 'shop_attachment');
                }
            }
        } else {
            // Handle Web image upload
            if ($request->hasFile('shop_attachment')) {
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->route('shop.create')
                    ->withErrors(['shop_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }

        $provider = $shop->provider;
        $createdByProvider = auth()->user()->hasRole('provider');
        
        // Create default shop hours for all days (Monday-Sunday)
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $userId = auth()->id();
        foreach ($days as $day) {
            ShopHour::create([
                'shop_id' => $shop->id,
                'day' => $day,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'is_holiday' => false,
                'breaks' => [],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
        
        try {
            $this->sendNotification([
                'activity_type'  => 'new_shop_created',
                'shop_id'        => $shop->id,
                'shop_name'      => $shop->shop_name,
                'provider_id'    => $shop->provider_id,
                'provider_name'  => $provider ? $provider->display_name : '',
                'created_by'     => $createdByProvider ? 'provider' : 'admin',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Shop created notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.save_form', ['form' => __('messages.shop')]),
        ], 201);
    }

    public function shopUpdate(ShopRequest $request, $id)
    {
        $shop = Shop::findOrFail($id);
        $data = $request->except(['service_ids', 'shop_attachment']);
      

        // Convert boolean is_active to integer (0 or 1)
        if (isset($data['is_active'])) {
            if ($data['is_active'] === 'false' || $data['is_active'] === false || $data['is_active'] === '0') {
                $data['is_active'] = 0;
            } elseif ($data['is_active'] === 'true' || $data['is_active'] === true || $data['is_active'] === '1') {
                $data['is_active'] = 1;
            }
        }

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode($sitesetup->value);
        $timezone = $sitesetupValue->time_zone ?? 'UTC';

        // `shop_start_time` / `shop_end_time` may be omitted by clients.
        // Only parse & update when present and non-empty; otherwise keep existing DB values.
        if (!empty($data['shop_start_time'] ?? null)) {
            $data['shop_start_time'] = Carbon::parse($data['shop_start_time'], $timezone)
                ->setTimezone('UTC')
                ->format('H:i:s');
        } else {
            unset($data['shop_start_time']);
        }

        if (!empty($data['shop_end_time'] ?? null)) {
            $data['shop_end_time'] = Carbon::parse($data['shop_end_time'], $timezone)
                ->setTimezone('UTC')
                ->format('H:i:s');
        } else {
            unset($data['shop_end_time']);
        }


        $oldActive = $shop->is_active;
        // Update shop
        $shop->update($data);

        $language_option = sitesetupSession('get')->language_option ?? ['ar', 'nl', 'en', 'fr', 'de', 'hi', 'it'];
        $translationsPayload = $this->shopTranslationsPayloadForSave($request);
        if ($translationsPayload !== null) {
            $shop->saveTranslations(['translations' => $translationsPayload], ['shop_name'], $language_option, 'en');
        }

        if (isset($data['is_active']) && (int) $data['is_active'] !== (int) $oldActive) {
            $provider = $shop->provider;
            $changedBy = auth()->user()->hasRole('provider') ? 'provider' : 'admin';
            $statusText = $shop->is_active ? __('messages.active') : __('messages.inactive');
            try {
                $this->sendNotification([
                    'activity_type'  => $request->status == 1 ? 'shop_activated' :  'shop_deactivated',
                    'shop_id'        => $shop->id,
                    'shop_name'      => $shop->shop_name,
                    'shop_status'    => $statusText,
                    'provider_id'    => $shop->provider_id,
                    'provider_name'  => $provider ? $provider->display_name : '',
                    'changed_by'     => $changedBy,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Shop status changed notification failed: ' . $e->getMessage());
            }
        }

        // Sync selected services
        if ($request->filled('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        }

        if ($request->is('api/*')) {
            // Handle API image upload
            $file = [];
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachmentKey = "shop_attachment_" . $i;
                    if ($request->$attachmentKey != null) {
                        $file[] = $request->$attachmentKey;
                    }
                }

                if (!empty($file)) {
                    // Optional: clear old media before adding new
                    $shop->clearMediaCollection('shop_attachment');
                    storeMediaFile($shop, $file, 'shop_attachment');
                }
            }
        } else {
            // Handle web image upload
            if ($request->hasFile('shop_attachment')) {
                $shop->clearMediaCollection('shop_attachment'); // Remove old images
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->route('shop.edit', $shop->id)
                    ->withErrors(['shop_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.update_form', ['form' => __('messages.shop')]),
        ], 201);
    }

    public function deleteShop($id)
    {

        $shop = Shop::find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        $shop->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.delete_form', ['form' => __('messages.shop')]),
        ], 201);
    }

    public function restoreShop($id)
    {
        $shop = Shop::onlyTrashed()->find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        $shop->restore();

        return response()->json([
            'status' => true,
            'message' => __('messages.msg_restored', ['name' => __('messages.shop')]),
        ], 201);
    }

    public function forceDeleteShop($id)
    {


        $shop = Shop::onlyTrashed()->find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }

        $shop->forceDelete();

        return response()->json([
            'status' => true,
            'message' => __('messages.delete_form', ['form' => __('messages.shop')]),
        ], 201);
    }
    public function getShopHoursList($id)
    {
        $shop = Shop::with('shopHours')->find($id);

        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }
        $shopHours = $shop->shopHours()->orderByRaw("FIELD(day, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")->get();
        if ($shopHours->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Shop hours not found.',
                'data' => []
            ], 200);
        }
        return response()->json([
            'status' => true,
            'message' => 'Shop hours list retrieved successfully.',
            'data' => ShopHourResource::collection($shopHours),
        ], 200);
    }
    /**
     * Insert or update shop hours for a shop (common API for create/update).
     * Expects JSON: { "shop_hours": [ { "day": "monday", "start_time": "09:00", "end_time": "18:00", "is_holiday": false, "breaks": [{ "start_break": "12:00", "end_break": "13:00" }] }, ... ] }
     */
    public function shopHoursStore(ShopHourStoreRequest $request, $id)
    {
        $shop = Shop::find($id);
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }
        if (auth()->user()->hasRole('user')) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to update this shop.'], 403);
        }
        if (auth()->user()->hasRole('provider') && (int) $shop->provider_id !== (int) auth()->id()) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to update this shop.'], 403);
        }

        $userId = auth()->id();
        $shopHours = $request->validated('shop_hours');

        foreach ($shopHours as $item) {
            $day = $item['day'];
            $start = $item['start_time'];
            $end = $item['end_time'];
            $start = strlen($start) === 5 ? $start . ':00' : $start;
            $end = strlen($end) === 5 ? $end . ':00' : $end;
            $isHoliday = !empty($item['is_holiday']);
            $breaksInput = $item['breaks'] ?? [];
            $breaks = [];
            if (is_array($breaksInput)) {
                foreach ($breaksInput as $b) {
                    $s = $b['start_break'] ?? '12:00';
                    $e = $b['end_break'] ?? '13:00';
                    $s = strlen($s) === 5 ? $s . ':00' : $s;
                    $e = strlen($e) === 5 ? $e . ':00' : $e;
                    $breaks[] = ['start_break' => $s, 'end_break' => $e];
                }
            }
            $payload = [
                'start_time' => $start,
                'end_time' => $end,
                'is_holiday' => $isHoliday,
                'breaks' => $breaks,
                'updated_by' => $userId,
            ];
            $existing = ShopHour::where('shop_id', (int) $id)->where('day', $day)->first();
            if ($existing) {
                $existing->update($payload);
            } else {
                ShopHour::create(array_merge($payload, [
                    'shop_id' => (int) $id,
                    'day' => $day,
                    'created_by' => $userId,
                ]));
            }
        }

        $shopHours = $shop->shopHours()->orderByRaw("FIELD(day, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")->get();
        return response()->json([
            'status' => true,
            'message' => __('messages.shop_hours_saved'),
            'data' => ShopHourResource::collection($shopHours),
        ], 200);
    }
}
