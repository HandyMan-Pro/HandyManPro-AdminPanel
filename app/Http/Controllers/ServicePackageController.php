<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\Service;
use App\Models\PackageServiceMapping;
use Yajra\DataTables\DataTables;
use App\Models\BookingPackageMapping;
use App\Traits\TranslationTrait;
use App\Models\Setting;
use App\Models\LoyaltyEarnServiceMapping;
use App\Models\LoyaltyRedeemServiceMapping;

class ServicePackageController extends Controller
{
    use TranslationTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get the row where type = service-configurations
        $setting = Setting::where('type', 'service-configurations')->first();

        $config = json_decode($setting->value ?? '{}', true);

        // If service_packages is not enabled, block access
        if (empty($config['service_packages']) || $config['service_packages'] != 1) {
            abort(403, 'Permission required to view this content.');
        }

        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.packages');
        $auth_user = authSession();
        $assets = ['datatable'];

        if ($request->loyality_earn_rule_id) {
            $id = $request->loyality_earn_rule_id;
            $type = "loyality_earn_rule";
        } else if ($request->loyality_redeem_rule_id) {
            $id = $request->loyality_redeem_rule_id;
            $type = "loyality_redeem_rule";
        } else {
            $id = null;
            $type = null;
        }

        return view('servicepackage.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'id', 'type'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = ServicePackage::query();
        $primary_locale = app()->getLocale() ?? 'en';
        $defaultLanguage = getDefaultLanguage();
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        $userId = auth()->user()->id;
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query = $query;
        } else if (auth()->user()->hasAnyRole(['provider'])) {
            $query = $query->where('service_packages.provider_id', $userId);
        }

         // ✅ Loyalty Earn Rule Filter
        if ($request->type === 'loyality_earn_rule' && $request->id) {
            $serviceIds = LoyaltyEarnServiceMapping::where('loyalty_earn_id', $request->id)
                ->pluck('service_id');

            $query->whereIn('id', $serviceIds);
        }

        if ($request->type === 'loyality_redeem_rule' && $request->id) {
            $serviceIds = LoyaltyRedeemServiceMapping::where('loyalty_redeem_id', $request->id)
                ->pluck('service_id');

            $query->whereIn('id', $serviceIds);
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {

                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('status', function ($query) {
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                <div class="custom-switch-inner">
                    <input type="checkbox" class="custom-control-input  change_status" data-type="servicepackage_status" ' . ($query->status ? "checked" : "") . '  value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                    <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                </div>
            </div>';
            })

            ->editColumn('name', function ($query) use ($defaultLanguage) {
                // Display name based on DEFAULT language setting (not current viewing language)
                if ($defaultLanguage === 'en') {
                    // English is default, show main column
                    $name = $query->name;
                } else {
                    // Non-English is default, try to get that language's translation
                    $translation = $query->translations()
                        ->where('locale', $defaultLanguage)
                        ->where('attribute', 'name')
                        ->value('value');
                    
                    $name = $translation ?: $query->name;
                }

                // Get the image URL(s) associated with the service package
                $imageUrls = getSingleMedia($query, 'package_attachment', null);

                // Ensure $imageUrls is an array, even if it's a single string
                if (!is_array($imageUrls)) {
                    $imageUrls = [$imageUrls];
                }

                // Initialize an empty string to hold the image tags
                $imageTags = '';

                // Iterate through the images and create image tags
                foreach ($imageUrls as $imageUrl) {
                    $imageTags .= '<img src="' . $imageUrl . '" alt="service package image" class="avatar avatar-40 rounded-pill mr-2">';
                }

                // Check if the user has the 'service list' permission
                if (auth()->user()->can('service list')) {
                    // Create a link for service package viewing with image and name
                    $link = '<a class="btn-link btn-link-hover" href="' . route('servicepackage.service', $query->id) . '">' . $imageTags . $name . '</a>';
                } else {
                    $link = $imageTags . $name;
                }

                return $link;
            })

            ->filterColumn('name', function ($query, $keyword) {
                // Search in main name column AND all translations
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhereHas('translations', function ($query) use ($keyword) {
                            // Search in ALL language translations
                            $query->where('attribute', 'name')
                                ->where('value', 'LIKE', '%' . $keyword . '%');
                        });
                });
            })
            ->editColumn('provider_id', function ($query) {
                return view('servicepackage.service', compact('query'));
            })
            ->filterColumn('provider_id', function ($query, $keyword) {
                $query->whereHas('providers', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->select('service_packages.*')
                    ->join('users as providers', 'providers.id', '=', 'service_packages.provider_id')
                    ->orderBy('providers.display_name', $order);
            })
            ->editColumn('category_id', function ($query) {
                return ($query->category_id != null && isset($query->category)) ? $query->category->name : '-';
            })
            ->editColumn('package_type', function ($query) {
                return ($query->package_type != null && isset($query->package_type)) ? ucfirst($query->package_type) : '-';
            })
            ->editColumn('price', function ($query) {
                return ($query->price != null && isset($query->price)) ? getPriceFormat($query->price) : '-';
            })
            ->addColumn('action', function ($servicepackage) {
                return view('servicepackage.action', compact('servicepackage'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status', 'name', 'check'])
            ->toJson();
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';


        switch ($actionType) {
            case 'change-status':
                $branches = ServicePackage::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Service Status Updated';
                break;

            case 'delete':
                ServicePackage::whereIn('id', $ids)->delete();
                $message = 'Bulk Service Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!auth()->user()->can('servicepackage add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $auth_user = authSession();
        $language_array = $this->languagesArray();
        $services = [];
        $selectedServiceId = [];
        $servicepackage = ServicePackage::find($id);
        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.package')]);
        if ($servicepackage !== null) {
            $serviceIds = $servicepackage->packageServices->pluck('service_id')->toArray();
            if (is_array($serviceIds)) {
                $services = Service::whereIn('id', $serviceIds)->get();
                $selectedServiceId = $serviceIds;
            }
        }
        if ($servicepackage == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.package')]);
            $servicepackage = new ServicePackage;
        } else {
            if ($servicepackage->provider_id !== auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
                return redirect(route('servicepackage.index'))->withErrors(trans('messages.demo_permission_denied'));
            }
        }

        return view('servicepackage.create', compact('pageTitle', 'servicepackage', 'auth_user', 'services', 'selectedServiceId', 'language_array'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate based on whether it's API or web request
        if ($request->is('api/*')) {
            $request->validate([
                'name' => 'required|unique:service_packages,name,' . ($request->id ?? '') . ',id',
            ]);
        } else {
            // For web requests, validate that at least one language has a name
            $hasName = false;
            $primary_locale = app()->getLocale() ?? 'en';
            
            // Check if default language name is provided
            if (!empty($request->name)) {
                $hasName = true;
            }
            
            // Check if any translation has a name
            if (!$hasName && isset($request->translations) && is_array($request->translations)) {
                foreach ($request->translations as $lang => $fields) {
                    if (!empty($fields['name'])) {
                        $hasName = true;
                        break;
                    }
                }
            }
            
            if (!$hasName) {
                return redirect()->back()
                    ->withErrors(['name' => __('messages.name_required_in_any_language')])
                    ->withInput();
            }
            
            // Validate uniqueness for the primary language name
            $nameToCheck = $request->name;
            if (empty($nameToCheck) && isset($request->translations[$primary_locale]['name'])) {
                $nameToCheck = $request->translations[$primary_locale]['name'];
            }
            
            if (!empty($nameToCheck)) {
                $existingPackage = ServicePackage::where('name', $nameToCheck)
                    ->where('id', '!=', $request->id ?? 0)
                    ->first();
                    
                if ($existingPackage) {
                    return redirect()->back()
                        ->withErrors(['name' => __('validation.unique', ['attribute' => 'name'])])
                        ->withInput();
                }
            }
        }
        $data = $request->all();
        $language_option = sitesetupSession('get')->language_option ?? ["ar", "nl", "en", "fr", "de", "hi", "it"];
        $defaultLanguage = getDefaultLanguage();
        $translatableAttributes = ['name', 'description'];
        
        $provider_id = !empty($request->provider_id) ? $request->provider_id : \Auth::user()->id;
        
        // Prepare data for main table
        $mainData = $request->except(array_merge(['package_attachment', 'translations'], $translatableAttributes));
        $mainData['provider_id'] = $provider_id;
        $mainData['is_featured'] = $request->has('is_featured') ? $request->is_featured : 0;
        
        // Main table always stores English. On UPDATE: preserve existing if English field is empty.
        // On CREATE: if English is empty, fall back to the first non-empty translation.
        foreach ($translatableAttributes as $attr) {
            $submitted = $request->input($attr, '');
            if (!empty($request->id) && trim($submitted) === '') {
                $existing = ServicePackage::find($request->id);
                $mainData[$attr] = $existing ? $existing->$attr : '';
            } elseif (trim($submitted) === '') {
                // No English value — use first available translation as fallback
                $fallback = '';
                if (isset($data['translations']) && is_array($data['translations'])) {
                    foreach ($data['translations'] as $locale => $fields) {
                        if (!empty($fields[$attr])) {
                            $fallback = $fields[$attr];
                            break;
                        }
                    }
                }
                $mainData[$attr] = $fallback;
            } else {
                $mainData[$attr] = $submitted;
            }
        }
        
        if (!$request->is('api/*')) {
            if ($request->id == null) {
                if (!isset($data['package_attachment'])) {
                    return redirect()->back()->withErrors(__('validation.required', ['attribute' => 'attachments']));
                }
            }
        }
        
        $primary_locale = app()->getLocale() ?? 'en';
        
        $result = ServicePackage::updateOrCreate(['id' => $request->id], $mainData);
        
        if ($request->is('api/*')) {
            $data['translations'] = json_decode($data['translations'] ?? '{}', true);
        } else {
            $data['translations'] = $request->input('translations', []);
        }
        
        $result->saveTranslations($data, $translatableAttributes, $language_option, $primary_locale);

        // if (!empty($request->service_id)) {
        //     $service = $request->service_id;
        //     if (!$request->is('api/*')) {
        //         $service = implode(",", $request->service_id);
        //     }
        //     foreach (explode(',', $service) as $key => $value) {
        //         $mapping_array = [
        //             'service_package_id' => $result->id,
        //             'service_id' => $value
        //         ];
        //         $result->packageServices()->create($mapping_array);
        //     }
        // }

        if (!empty($request->service_id)) {
            if ($result->packageServices()->count() > 0) {
                $result->packageServices()->delete();
            }
            $service = $request->service_id;

            // For web, convert array to comma-separated string
            if (!$request->is('api/*') && is_array($service)) {
                $service = implode(",", $service);
            }

            $serviceItems = is_string($service) ? explode(',', $service) : $service;
            $serviceItems = array_unique($serviceItems); // remove duplicates in input

            // Get existing mapped service IDs
            $existingServiceIds = $result->packageServices()->pluck('service_id')->toArray();

            foreach ($serviceItems as $value) {
                if ($value && !in_array($value, $existingServiceIds)) {
                    // Only create if mapping does not exist
                    $result->packageServices()->create([
                        'service_package_id' => $result->id,
                        'service_id' => $value
                    ]);
                }
            }
        }
        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "package_attachment_" . $i;
                    if ($request->$attachment != null) {
                        $file[] = $request->$attachment;
                    }
                }
                storeMediaFile($result, $file, 'package_attachment');
            }
        } else {

            if ($request->hasFile('package_attachment')) {
                storeMediaFile($result, $request->package_attachment, 'package_attachment');
            } elseif (!getMediaFileExit($result, 'package_attachment')) {
                return redirect()->route('servicepackage.create', ['id' => $result->id])
                    ->withErrors(['package_attachment' => 'The attachments field is required.'])
                    ->withInput();
            }
        }

        $message = trans('messages.update_form', ['form' => trans('messages.package')]);
        if ($result->wasRecentlyCreated) {
            $message = trans('messages.save_form', ['form' => trans('messages.package')]);
        }
        if ($request->is('api/*')) {
            return comman_message_response($message);
        }
        return redirect(route('servicepackage.index'))->withSuccess($message);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (demoUserPermission()) {
            if (request()->is('api/*')) {
                return comman_message_response(__('messages.demo_permission_denied'));
            }
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $service_package = ServicePackage::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.package')]);

        if ($service_package != '') {

            $service_package->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.package')]);
        }
        if (request()->is('api/*')) {
            return comman_custom_response(['message' => $msg, 'status' => true]);
        }
        return redirect()->back()->withSuccess($msg);
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $servicepackage = ServicePackage::where('id', $id)->first();
        $msg = __('messages.not_found_entry', ['name' => __('messages.service_package')]);
        if ($request->type === 'forcedelete') {
            $bookingPackageMappings = $servicepackage->bookingPackageMappings;
            foreach ($bookingPackageMappings as $bookingPackageMapping) {
                $booking = $bookingPackageMapping->bookings;
                if ($booking) {
                    $booking->delete();
                }
                $bookingPackageMapping->delete();
            }
            $servicepackage->forceDelete();
            $msg = __('messages.msg_forcedelete', ['name' => __('messages.service_package')]);
        }

        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
}
