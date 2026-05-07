<?php

namespace App\Http\Controllers;


use App\Models\City;
use App\Models\Shop;
use App\Models\ShopHour;
use App\Models\User;
use App\Models\State;
use App\Models\Country;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ShopRequest;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Setting;
use App\Traits\NotificationTrait;
use App\Traits\TranslationTrait;

class ShopController extends Controller
{
    use NotificationTrait, TranslationTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = [
            'column_status' => $request->get('column_status', ''),
        ];

        if ($request->provider_id) {
            $id = $request->provider_id;
            $type = "provider-shop";
        } else {
            $id = null;
            $type = null;
        }

        return view('shop.index', compact('filter', 'id', 'type'));
    }

    public function index_data(Request $request)
    {
        $query = Shop::with(['country', 'state', 'city', 'provider'])->whereHas('provider')
            ->withTrashed()
            ->when(auth()->user()->hasRole('provider'), function ($q) {
                $q->where('shops.provider_id', auth()->id());
            });

        if (auth()->user()->hasRole('provider')) {
            $query->where('shops.provider_id', auth()->id());
        }

        if ($request->type === 'provider-shop') {
            $query->where('shops.provider_id', $request->id);
        }

        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $query->where('is_active', $filter['column_status']);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        // Search in main shop_name column AND all translations
                        $q->where('shop_name', 'like', "%{$search}%")
                            ->orWhereHas('translations', function ($q2) use ($search) {
                                // Search in ALL language translations for shop_name
                                $q2->where('attribute', 'shop_name')
                                    ->where('value', 'LIKE', "%{$search}%");
                            })
                            ->orWhere('contact_number', 'like', "%{$search}%")
                            ->orWhereHas('city', function ($q2) use ($search) {
                                $q2->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('provider', function ($q3) use ($search) {
                                $q3->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="shop" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('shop_name', function ($shop) {
                $translatedName = $shop->translate('shop_name');
                $shopHtml = '
                    <div class="d-flex gap-3 align-items-center">
                        <img src="' . getSingleMedia($shop, 'shop_attachment', null) . '" alt="service" class="avatar avatar-40 rounded-pill">
                        <div class="text-start">
                            <h6 class="m-0">' . e($translatedName) . '</h6>
                            <span>' . e($shop->email ?? '--') . '</span>
                        </div>
                    </div>
                ';

                if (is_null($shop->deleted_at)) {
                    return '<a href="' . route('shop.show', $shop->id) . '">' . $shopHtml . '</a>';
                } else {
                    return $shopHtml;
                }
            })
            ->orderColumn('shop_name', function ($query, $order) {
                $query->orderBy('shop_name', $order);
            })
            ->editColumn('provider_id', function ($shop) {
                return '<a href="' . route('provider_info', $shop->provider->id) . '">
                    <div class="d-flex gap-3 align-items-center">
                        <img src="' . getSingleMedia($shop->provider, 'profile_image', null) . '" alt="avatar" class="avatar avatar-40 rounded-pill">
                        <div class="text-start">
                            <h6 class="m-0">' . e($shop->provider->first_name) . ' ' . e($shop->provider->last_name) . '</h6>
                            <span>' . e($shop->provider->email ?? '--') . '</span>
                        </div>
                    </div>
                </a>';
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->leftJoin('users as providers', 'shops.provider_id', '=', 'providers.id')
                    ->whereColumn('shops.provider_id', 'providers.id')
                    ->orderBy('providers.first_name', $order)
                    ->orderBy('providers.last_name', $order)
                    ->select('shops.*');
            })
            ->editColumn('city_id', function ($shop) {
                return $shop->city?->name ?? '';
            })
            ->orderColumn('city_id', function ($query, $order) {
                $query->leftJoin('cities as c', 'shops.city_id', '=', 'c.id')
                    ->orderBy('c.name', $order)
                    ->select('shops.*');
            })
            ->editColumn('contact_number', function ($shop) {
                return e($shop->contact_number);
            })
            ->editColumn('is_active', function ($shop) {
                $disabled = $shop->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="shop_status" ' . ($shop->is_active == 1 ? "checked" : "") . ' ' . $disabled . ' value="' . $shop->id . '" id="' . $shop->id . '" data-id="' . $shop->id . '">
                        <label class="custom-control-label" for="' . $shop->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($shop) {
                return view('shop.action', compact('shop'))->render();
            })
            ->rawColumns(['check', 'shop_name', 'provider_id', 'city_id', 'contact_number', 'is_active', 'action'])
            ->toJson();
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                $shops = Shop::whereIn('id', $ids)->get();
                Shop::whereIn('id', $ids)->update(['is_active' => $request->status]);
                $statusText = $request->status ? __('messages.active') : __('messages.inactive');
                foreach ($shops as $shop) {
                    $provider = $shop->provider;
                    try {
                        $this->sendNotification([
                            'activity_type'  => $request->status == 1 ? 'shop_activated' :  'shop_deactivated',
                            'shop_id'        => $shop->id,
                            'shop_name'      => $shop->shop_name,
                            'shop_status'    => $statusText,
                            'provider_id'    => $shop->provider_id,
                            'provider_name'  => $provider ? $provider->display_name : '',
                            'changed_by'     => 'admin',
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Shop status changed notification failed: ' . $e->getMessage());
                    }
                }
                $message = 'Bulk Shop Status Updated';
                break;

            case 'delete':
                Shop::whereIn('id', $ids)->delete();
                $message = 'Bulk Shop Deleted';
                break;

            case 'restore':
                Shop::whereIn('id', $ids)->restore();
                $message = 'Bulk Shop Restored';
                break;

            case 'permanently-delete':
                Shop::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Shop Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (auth()->user()->hasRole('provider')) {
            $shop = Shop::with(['country', 'state', 'city', 'provider', 'services'])->findOrFail($id);
            if ($shop->provider_id !== auth()->id()) {
                return redirect()->route('shop.index')->with('success', 'Shop Not found.');
            }
        }

        $shop = Shop::with(['country', 'state', 'city', 'provider', 'services'])->findOrFail($id);
        return view('shop.show', compact('shop'));
    }

    public function create()
    {
        $url = route('shop.store');
        $countries = Country::select('id', 'name')->get();
        $language_array = $this->languagesArray();
        $shop = null;
        return view('shop.form', compact('url', 'countries', 'language_array', 'shop'));
    }
    public function edit($id)
    {
        if (auth()->user()->hasRole('provider')) {
            $shop = Shop::with(['country', 'state', 'city', 'provider', 'services'])->findOrFail($id);
            if ($shop->provider_id !== auth()->id()) {
                return redirect()->route('shop.index')->with('success', 'Shop Not found.');
            }
        }

        $url = route('shop.update', $id);
        $countries = Country::select('id', 'name')->get();
        $shop = Shop::findOrFail($id);
        // Format start/end time as per site settings timezone & format
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode(optional($sitesetup)->value ?? '{}');
        $targetTimezone = isset($sitesetupValue->time_zone) ? trim((string) $sitesetupValue->time_zone) : 'UTC';
        $timeFormat = $sitesetupValue->time_format ?? 'H:i';

        $shop['shop_start_time'] = $shop->getRawOriginal('shop_start_time')
            ? Carbon::parse($shop->getRawOriginal('shop_start_time'), 'UTC')->setTimezone($targetTimezone)->format($timeFormat)
            : null;
        $shop['shop_end_time'] = $shop->getRawOriginal('shop_end_time')
            ? Carbon::parse($shop->getRawOriginal('shop_end_time'), 'UTC')->setTimezone($targetTimezone)->format($timeFormat)
            : null;

        $language_array = $this->languagesArray();
        return view('shop.form', compact('url', 'countries', 'shop', 'language_array'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShopRequest $request)
    {
        $language_option = sitesetupSession('get')->language_option ?? ['ar', 'nl', 'en', 'fr', 'de', 'hi', 'it'];
        $defaultLanguage = getDefaultLanguage();
        $translatableAttributes = ['shop_name'];

        // Prepare data for main table
        $mainData = $request->except(array_merge(['service_ids', 'shop_attachment', 'translations'], $translatableAttributes));
        
        // Main table always stores English. On UPDATE: preserve existing if English field is empty.
        foreach ($translatableAttributes as $attr) {
            $submitted = $request->input($attr, '');
            if ($request->input('id') && trim($submitted) === '') {
                $existing = Shop::find($request->input('id'));
                $mainData[$attr] = $existing ? $existing->$attr : '';
            } else {
                $mainData[$attr] = $submitted;
            }
        }

        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode($sitesetup->value);
        $timezone = $sitesetupValue->time_zone ?? 'UTC';

        if (!empty($request->shop_start_time)) {
            $mainData['shop_start_time'] = Carbon::parse($request->shop_start_time, $timezone)
                ->setTimezone('UTC')
                ->format('H:i:s');
        }

        if (!empty($request->shop_end_time)) {
            $mainData['shop_end_time'] = Carbon::parse($request->shop_end_time, $timezone)
                ->setTimezone('UTC')
                ->format('H:i:s');
        }

        $shop = Shop::create($mainData);
        
        $primary_locale = app()->getLocale() ?? 'en';
        
        if ($request->is('api/*')) {
            $data['translations'] = json_decode($request->translations ?? '{}', true);
        } else {
            $data['translations'] = $request->input('translations', []);
        }
        
        $shop->saveTranslations($data, $translatableAttributes, $language_option, $primary_locale);
        
        if ($request->has('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        }
        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "shop_attachment_" . $i;
                    if ($request->$attachment != null) {
                        $file[] = $request->$attachment;
                    }
                }
                storeMediaFile($shop, $file, 'shop_attachment');
            }
        } else {
            if ($request->hasFile('shop_attachment')) {
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->route('shop.create')
                    ->withErrors(['shop_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }

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

        // Return success immediately - notifications will be sent in background if possible
        $successMessage = __('messages.save_form', ['form' => __('messages.shop')]);
        
        // Try to send notifications, but don't block the response
        try {
            $provider = $shop->provider;
            $createdByProvider = auth()->user()->hasRole('provider');
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
            // Don't show error to user - shop was created successfully
        }

        return redirect()->route('shop.index')->with('success', $successMessage);
    }
    public function update(ShopRequest $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $data = $request->except(['service_ids', 'shop_attachment']);

           $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $sitesetupValue = json_decode($sitesetup->value);
    $timezone = $sitesetupValue->time_zone ?? 'UTC';

        if (!empty($request->shop_start_time)) {
            $data['shop_start_time'] = Carbon::parse($request->shop_start_time, $timezone)
                ->setTimezone('UTC')
                ->format('H:i:s');
        } else {
            // Don't overwrite existing values with null/empty on edit.
            unset($data['shop_start_time']);
        }

        if (!empty($request->shop_end_time)) {
            $data['shop_end_time'] = Carbon::parse($request->shop_end_time, $timezone)
                ->setTimezone('UTC')
                ->format('H:i:s');
        } else {
            unset($data['shop_end_time']);
        }

        $oldActive = $shop->is_active;
        $shop->update($data);
        $language_option = sitesetupSession('get')->language_option ?? ['ar', 'nl', 'en', 'fr', 'de', 'hi', 'it'];
        if (!$request->is('api/*') && isset($request->translations) && is_array($request->translations)) {
            $shop->saveTranslations($request->all(), ['shop_name'], $language_option, 'en');
        }

        if ($request->filled('service_ids')) {
            $shop->services()->sync($request->input('service_ids'));
        } else {
            $shop->services()->sync([]);
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

        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                $files = [];
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachmentKey = 'shop_attachment_' . $i;
                    if (!empty($request->$attachmentKey)) {
                        $files[] = $request->$attachmentKey;
                    }
                }
                if (!empty($files)) {
                    storeMediaFile($shop, $files, 'shop_attachment');
                }
            }
        } else {
            if ($request->hasFile('shop_attachment')) {
                storeMediaFile($shop, $request->file('shop_attachment'), 'shop_attachment');
            } elseif (!getMediaFileExit($shop, 'shop_attachment')) {
                return redirect()->back()
                    ->withErrors(['shop_attachment' => 'The shop image is required.'])
                    ->withInput();
            }
        }

        return redirect()->route('shop.index')
            ->with('success', __('messages.update_form', ['form' => __('messages.shop')]));
    }

    /**
     * Remove the specified resource from storage.
     **/
    public function delete($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();
        $msg = __('messages.delete_form', ['form' => __('messages.shop')]);
        return response()->json(['message' => $msg, 'status' => true]);
    }
    public function restore($id)
    {
        $shop = Shop::withTrashed()->findOrFail($id);
        $shop->restore();
        $msg = __('messages.msg_restored', ['name' => __('messages.shop')]);
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $shop = Shop::withTrashed()->findOrFail($id);
        $shop->forceDelete();
        $msg = __('messages.delete_form', ['form' => __('messages.shop')]);
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Get states for a specific country.
     */

    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)->pluck('name', 'id');
        return response()->json($states);
    }


    /**
     * Get cities for a specific state.
     */
    public function getCities($stateId)
    {
        $cities = City::where('state_id', $stateId)->pluck('name', 'id');
        return response()->json($cities);
    }

    /**
     * Get all providers with their shops.
     */
   public function getProviders()
    {
        $providers = User::where('user_type','provider')->where('status', 1)->get();

        return response()->json($providers);
    }

    /**
     * Get all services for a specific provider.
     */
    public function getServices($providerId)
    {
        $services = Service::where('provider_id', $providerId)->select('id', 'name')->where('status',1)->where('service_request_status','approve')->get();
        return response()->json($services);
    }

    public function checkRegistration(Request $request)
    {
        $field = $request->field;
        $value = $request->value;

        if (!in_array($field, ['contact_number', 'email', 'registration_number'])) {
            return response()->json(['status' => false, 'message' => 'Invalid field']);
        }

        $exists = \App\Models\Shop::where($field, $value)->exists();

        return response()->json([
            'status' => !$exists,
            'message' => $exists ? ucfirst(str_replace('_', ' ', $field)) . ' already exists' : ''
        ]);
    }

    /**
     * Show Shop Hours form for a shop (branch).
     */
    public function manageHour($id)
    {
        $shop = Shop::findOrFail($id);
        if (auth()->user()->hasRole('provider') && $shop->provider_id !== auth()->id()) {
            return redirect()->route('shop.index')->withErrors(__('messages.not_found_entry', ['name' => __('messages.shop')]));
        }
        $branches = Shop::when(auth()->user()->hasRole('provider'), function ($q) {
            $q->where('provider_id', auth()->id());
        })->orderBy('shop_name')->get(['id', 'shop_name']);
        $shopHoursByDay = $shop->shopHours()->get()->keyBy('day');
        return view('shop.shop-hours', compact('shop', 'branches', 'shopHoursByDay'));
    }

    /**
     * Store Business Hours for a shop (branch).    
     */
    public function storeManageHour(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);
        if (auth()->user()->hasRole('provider') && $shop->provider_id !== auth()->id()) {
            return redirect()->route('shop.index')->withErrors(__('messages.not_found_entry', ['name' => __('messages.shop')]));
        }
        $shopId = (int) $request->input('branch_id', $id);
        if ($shopId !== (int) $shop->id) {
            return redirect()->route('shop.manage-hour', $id)->withErrors(__('messages.not_found_entry', ['name' => __('messages.shop')]));
        }
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $errors = [];

        foreach ($days as $day) {
            $hours = $request->input("hours.{$day}", []);
            $start = $hours['start'] ?? '09:00';
            $end = $hours['end'] ?? '18:00';
            $isHoliday = !empty($hours['day_off']);

            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $start)) {
                $errors["hours.{$day}.start"] = __('messages.shop_hours_invalid_time');
            }
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $end)) {
                $errors["hours.{$day}.end"] = __('messages.shop_hours_invalid_time');
            }
            if (empty($errors["hours.{$day}.start"]) && empty($errors["hours.{$day}.end"]) && !$isHoliday) {
                $startSec = strtotime($start);
                $endSec = strtotime($end);
                if ($endSec <= $startSec) {
                    $errors["hours.{$day}.end"] = __('messages.shop_hours_end_after_start', ['day' => ucfirst($day)]);
                }
            }

            $breaksInput = $hours['breaks'] ?? [];
            if (is_array($breaksInput)) {
                $breakTimes = [];
                foreach ($breaksInput as $idx => $b) {
                    $s = $b['start'] ?? '12:00';
                    $e = $b['end'] ?? '13:00';
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $s)) {
                        $errors["hours.{$day}.breaks.{$idx}.start"] = __('messages.shop_hours_invalid_time');
                    }
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $e)) {
                        $errors["hours.{$day}.breaks.{$idx}.end"] = __('messages.shop_hours_invalid_time');
                    }
                    
                    // Only validate duplicates/overlaps if time format is valid
                    if (empty($errors["hours.{$day}.breaks.{$idx}.start"]) && empty($errors["hours.{$day}.breaks.{$idx}.end"])) {
                        if (strtotime($e) <= strtotime($s)) {
                            $errors["hours.{$day}.breaks.{$idx}.end"] = __('messages.shop_hours_break_end_after_start', ['day' => ucfirst($day)]);
                        } else {
                            // Normalize time format for comparison
                            $sNormalized = $this->normalizeBreakTime($s);
                            $eNormalized = $this->normalizeBreakTime($e);
                            
                            // Check break is within day working hours (skip if day off)
                            if (!$isHoliday && !empty($start) && !empty($end)) {
                                $dayStartNormalized = $this->normalizeBreakTime($start);
                                $dayEndNormalized = $this->normalizeBreakTime($end);
                                $dayStartSec = strtotime($dayStartNormalized);
                                $dayEndSec = strtotime($dayEndNormalized);
                                $breakStartSec = strtotime($sNormalized);
                                $breakEndSec = strtotime($eNormalized);
                                if ($breakStartSec < $dayStartSec) {
                                    $errors["hours.{$day}.breaks.{$idx}.start"] = 'Break start time must be on or after the day start time (' . $start . ') for ' . ucfirst($day) . '.';
                                } elseif ($breakEndSec > $dayEndSec) {
                                    $errors["hours.{$day}.breaks.{$idx}.end"] = 'Break end time must be on or before the day end time (' . $end . ') for ' . ucfirst($day) . '.';
                                }
                            }
                            
                            // Check for duplicate break times (only if no within-day errors)
                            if (empty($errors["hours.{$day}.breaks.{$idx}.start"]) && empty($errors["hours.{$day}.breaks.{$idx}.end"])) {
                                $breakKey = $sNormalized . '-' . $eNormalized;
                                if (isset($breakTimes[$breakKey])) {
                                    $errorMsg = __('messages.shop_hours_duplicate_break', ['day' => ucfirst($day), 'time' => $s . ' - ' . $e]);
                                    if ($errorMsg === 'messages.shop_hours_duplicate_break') {
                                        $errorMsg = 'Duplicate break time found for ' . ucfirst($day) . '. Break time ' . $s . ' - ' . $e . ' already exists.';
                                    }
                                    $errors["hours.{$day}.breaks.{$idx}.start"] = $errorMsg;
                                } else {
                                    $breakTimes[$breakKey] = $idx;
                                    foreach ($breakTimes as $existingKey => $existingIndex) {
                                        if ($existingIndex !== $idx) {
                                            [$existingStart, $existingEnd] = explode('-', $existingKey);
                                            if ($this->breaksOverlap($sNormalized, $eNormalized, $existingStart, $existingEnd)) {
                                                $errorMsg = __('messages.shop_hours_overlapping_break', ['day' => ucfirst($day)]);
                                                if ($errorMsg === 'messages.shop_hours_overlapping_break') {
                                                    $errorMsg = 'Overlapping break times found for ' . ucfirst($day) . '. Break periods cannot overlap.';
                                                }
                                                $errors["hours.{$day}.breaks.{$idx}.start"] = $errorMsg;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            return redirect()->route('shop.manage-hour', $id)->withInput($request->input())->withErrors($errors);
        }

        $userId = auth()->id();
        foreach ($days as $day) {
            $hours = $request->input("hours.{$day}", []);
            $start = $hours['start'] ?? '09:00';
            $end = $hours['end'] ?? '18:00';
            $start = strlen($start) === 5 ? $start . ':00' : $start;
            $end = strlen($end) === 5 ? $end . ':00' : $end;
            $isHoliday = !empty($hours['day_off']) ? true : false;
            $breaksInput = $hours['breaks'] ?? [];
            $breaks = [];
            if (is_array($breaksInput)) {
                foreach ($breaksInput as $b) {
                    $s = $b['start'] ?? '12:00';
                    $e = $b['end'] ?? '13:00';
                    $s = strlen($s) === 5 ? $s . ':00' : $s;
                    $e = strlen($e) === 5 ? $e . ':00' : $e;
                    $breaks[] = ['start_break' => $s, 'end_break' => $e];
                }
            }
            ShopHour::updateOrCreate(
                ['shop_id' => $shopId, 'day' => $day],
                [
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_holiday' => $isHoliday,
                    'breaks' => $breaks,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );
        }

        return redirect()->route('shop.manage-hour', $id)->with('success', __('messages.shop_hours_saved'));
    }

    /**
     * Normalize time format for comparison (ensure consistent format)
     */
    private function normalizeBreakTime(string $time): string
    {
        // Remove AM/PM if present and convert to 24-hour format
        $time = trim($time);
        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = $matches[2];
            $ampm = strtoupper($matches[3]);
            
            if ($ampm === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            return sprintf('%02d:%02d:00', $hour, $minute);
        }
        
        // If already in 24-hour format, ensure seconds are present
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time)) {
            return $time . ':00';
        }
        
        return $time;
    }

    /**
     * Check if two break periods overlap
     */
    private function breaksOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1Time = strtotime($start1);
        $end1Time = strtotime($end1);
        $start2Time = strtotime($start2);
        $end2Time = strtotime($end2);
        
        // Check if breaks overlap (not just exact duplicates)
        return ($start1Time < $end2Time && $start2Time < $end1Time);
    }
}
