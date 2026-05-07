<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Service;
use App\Models\Setting;
use App\Models\ServiceZone;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Traits\TranslationTrait;
use Yajra\DataTables\DataTables;
use App\Traits\NotificationTrait;
use App\Mail\ServiceStatusUpdated;
use App\Models\ServiceZoneMapping;
use Illuminate\Support\Facades\DB;
use App\Models\ProviderZoneMapping;
// use App\Models\ServiceZoneMapping;
use Illuminate\Support\Str;
use App\Http\Requests\ServiceRequest;
use App\Models\LoyaltyEarnServiceMapping;
use App\Models\LoyaltyRedeemServiceMapping;


class ServiceController extends Controller
{
    use NotificationTrait, TranslationTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $servicepackage = $request->packageid;
        $postrequestid = $request->route('postjobid');

        $auth_user = auth()->user();

        if ($request->is('servicepackage/list/*')) {
            if ($auth_user->hasRole('provider')) {
                $isAuthorizedPackage = ServicePackage::where('id', $servicepackage)
                    ->where('provider_id', $auth_user->id)
                    ->exists();

                if (!$isAuthorizedPackage) {
                    return redirect()->back()->withErrors(__('You are not authorized to view this package.'));
                }
            }
        }

        // When earning type is commission and demo_login=1, enforce all services to status=1
        if (default_earning_type() === 'commission' && $auth_user->demo_login == 1) {
            \App\Models\Service::query()->update(['status' => 1]);
        }

        $filter = [
            'status' => $request->status,
        ];

        if ($request->loyality_earn_rule_id) {
            $id = $request->loyality_earn_rule_id;
            $type = "loyality_earn_rule";
        } elseif ($request->loyality_redeem_rule_id) {
            $id = $request->loyality_redeem_rule_id;
            $type = "loyality_redeem_rule";
        } else {
            $id = null;
            $type = null;
        }

        $pageTitle = __('messages.all_form_title', ['form' => __('messages.services')]);
        $assets = ['datatable'];
        $zone_id = $request->zone_id;
        $globalSeoSetting = \App\Models\SeoSetting::first();
        return view('service.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'postrequestid', 'servicepackage', 'zone_id', 'globalSeoSetting', 'id', 'type'));
    }

    // get datatable data
    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Service::query()->where('service_request_status', 'approve')->myService()->orderBy('id','desc'); 
        
        $primary_locale = app()->getLocale() ?? 'en';
        $zone_id = $request->input('zone_id');

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin', 'provider'])) {
            $query = $query->where('service_type', 'service')->withTrashed();
        }
        if ($request->has('postrequestid')) {
            $postRequestId = $request->postrequestid;
            $query = Service::whereHas('postJobService', function ($query) use ($postRequestId) {
                $query->where('post_request_id', $postRequestId);
            });
        }
        if ($request->has('servicepackage')) {
            $servicepackage = $request->servicepackage;
            $query = Service::whereHas('servicePackage', function ($query) use ($servicepackage) {
                $query->where('service_package_id', $servicepackage);
            });
        }

        if ($request->has('zone_id') && $request->zone_id != null) {
            $zone_id = $request->zone_id;
            $query = Service::whereHas('serviceZoneMapping', function ($query) use ($zone_id) {
                $query->where('zone_id', $zone_id);
            });
        }

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

                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="service" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })

            // ->editColumn('name', function ($query) use ($primary_locale) {
            //     $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?? $query->name;

            //     if (auth()->user()->can('service edit')) {
            //         return '<a class="btn-link btn-link-hover" href="' . route('service.create', ['id' => $query->id]) . '">' . $name . '</a>';
            //     }

            //     return $name ?? '-';
            // })

            ->editColumn('name', function ($query) use ($primary_locale) {
                // Get the translated name, fall back to English main column if no translation exists
                $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?: $query->name;

                // Get the image URL(s) associated with the service (assuming 'service_image' is the media field)
                $imageUrls = getSingleMedia($query, 'service_attachment', null);

                // Ensure $imageUrls is an array, even if it's a single string
                if (!is_array($imageUrls)) {
                    $imageUrls = [$imageUrls]; // If it's a string, make it an array with one image
                }

                // Initialize an empty string to hold the image tags
                $imageTags = '';

                // Iterate through the images and create image tags
                foreach ($imageUrls as $imageUrl) {
                    $imageTags .= '<img src="' . $imageUrl . '" alt="service image" class="avatar avatar-40 rounded-pill mr-2">';
                }

                // Check if the user has the 'service edit' permission
                if (auth()->user()->can('service edit')) {
                    // Create a link for service editing
                    $link = '<a class="btn-link btn-link-hover" href="' . route('service.create', ['id' => $query->id]) . '">' . $name . '</a>';
                } else {
                    $link = $name;
                }

                // Return both the image and name (or link) with flexbox for alignment
                return '<div style="display: flex; align-items: center;">' .
                    $imageTags .
                    '<span style="margin-left: 10px;">' . $link . '</span>' .
                    '</div>';
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
            // ->editColumn('category_id', function ($query) use ($primary_locale) {
            //     $catname = $this->getTranslation(optional($query->category)->translations, $primary_locale, 'name', optional($query->category)->name) ?? optional($query->category)->name;

            //     return $catname ?? '-';
            //     //return ($query->category_id != null && isset($query->category)) ? $query->category->name : '-';
            // })
            ->editColumn('category_id', function ($query) {
                // Get the associated category
                $category = $query->category;

                // Return the view and pass the category data
                return view('subcategory.category', compact('category'));
            })
            ->filterColumn('category_id', function ($query, $keyword) use ($primary_locale) {
                // $query->whereHas('category',function ($q) use($keyword){
                //     $q->where('name','like','%'.$keyword.'%');
                // });
                $query->whereHas('category', function ($q) use ($keyword, $primary_locale) {
                    // Check if the locale is not 'en'
                    if ($primary_locale !== 'en') {
                        $q->where(function ($q) use ($keyword, $primary_locale) {
                            // Search in the translations table for the given locale
                            $q->whereHas('translations', function ($q) use ($keyword, $primary_locale) {
                                $q->where('locale', $primary_locale)
                                    ->where('value', 'LIKE', '%' . $keyword . '%');
                            })
                                // Fallback to checking 'name' field if no translation is found
                                ->orWhere('name', 'LIKE', '%' . $keyword . '%');
                        });
                    } else {
                        // If locale is 'en', search directly in the 'name' field
                        $q->where('name', 'LIKE', '%' . $keyword . '%');
                    }
                });
            })
            ->orderColumn('category_id', function ($query, $order) {
                $query->join('categories', 'categories.id', '=', 'services.category_id')
                    ->orderBy('categories.name', $order);
            })
            ->editColumn('provider_id', function ($query) {
                return view('service.service', compact('query'));
            })
            ->filterColumn('provider_id', function ($query, $keyword) {
                $query->whereHas('providers', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->select('services.*')
                    ->join('users as providers', 'providers.id', '=', 'services.provider_id')
                    ->orderBy('providers.display_name', $order);
            })
            ->editColumn('price', function ($query) {
                return getPriceFormat($query->price) . '-' . ucFirst($query->type);
            })

            ->editColumn('discount', function ($query) {
                return $query->discount ? $query->discount . '%' : '-';
            })
            ->addColumn('action', function ($data) {
                return view('service.action', compact('data'));
            })
            ->editColumn('status', function ($query) {
                $disabled = $query->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="service_status" ' . ($query->status ? "checked" : "") . '  ' . $disabled . ' value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })

            ->rawColumns(['action', 'status', 'check', 'name'])
            ->toJson();
    }

    public function request_index_data(DataTables $datatable, Request $request)
    {
        $query = Service::query()->where('is_service_request', 1)->where('service_request_status', '!=', 'approve')->myService();

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                if ($filter['column_status'] === 'pending') {
                    $query->where('service_request_status', 'pending');
                } elseif ($filter['column_status'] === 'reject') {
                    $query->where('service_request_status', 'reject');
                } elseif ($filter['column_status'] === 'approve') {
                    $query->where('service_request_status', 'approve');
                }
            }
        }
        if (auth()->user()->hasAnyRole(['admin', 'provider'])) {
            $query = $query->where('service_type', 'service')->withTrashed();
        }
        if ($request->has('postrequestid')) {
            $postRequestId = $request->postrequestid;
            $query = Service::whereHas('postJobService', function ($query) use ($postRequestId) {
                $query->where('post_request_id', $postRequestId);
            });
        }
        if ($request->has('servicepackage')) {
            $servicepackage = $request->servicepackage;
            $query = Service::whereHas('servicePackage', function ($query) use ($servicepackage) {
                $query->where('service_package_id', $servicepackage);
            });
        }



        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $row->id . '" name="datatable_ids[]" value="' . $row->id . '" data-type="service" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            // ->editColumn('name', function ($query) {
            //     $primary_locale = app()->getLocale();
            //     $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?? $query->name;
            //     if (auth()->user()->can('service edit')) {
            //         $link = '<a class="btn-link btn-link-hover" href="' . route('service.create', ['id' => $query->id]) . '">' . $name . '</a>';
            //     } else {
            //         $link = $query->name;
            //     }
            //     return $link;
            // })

            ->editColumn('name', function ($query) {
                $primary_locale = app()->getLocale();

                // Get translated name, fall back to English main column if no translation exists
                $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?: $query->name;

                // Get the image (assuming service_image is the media collection)
                $imageUrl = getSingleMedia($query, 'service_attachment', null);

                // Default placeholder if no image
                $imageTag = '<img src="' . $imageUrl . '" alt="service image" class="avatar avatar-40 rounded-pill mr-2">';

                // Wrap with link if user has permission
                if (auth()->user()->can('service edit')) {
                    $link = '<a class="btn-link btn-link-hover d-flex align-items-center"
                                href="' . route('service.create', ['id' => $query->id]) . '">'
                                . $imageTag . '<span>' . e($name) . '</span></a>';
                } else {
                    $link = '<div class="d-flex align-items-center">' . $imageTag . '<span>' . e($name) . '</span></div>';
                }

                return $link;
            })


            // ->editColumn('name', function ($query) {
            //     // Get the primary locale
            //     $primary_locale = app()->getLocale();

            //     // Get the translated name (fallback to default if not found)
            //     $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?? $query->name;

            //     // Get single image URL
            //     $imageUrl = getSingleMedia($query, 'service_image', null);

            //     // Build image tag (if exists)
            //     $imageTag = '';
            //     if ($imageUrl) {
            //         $imageTag = '<img src="' . $imageUrl . '" alt="service image" class="avatar avatar-40 rounded-pill mr-2">';
            //     }

            //     // Check if user has permission
            //     if (auth()->user()->can('service edit')) {
            //         $link = '<a class="btn-link btn-link-hover" href="' . route('service.create', ['id' => $query->id]) . '">' . e($name) . '</a>';
            //     } else {
            //         $link = e($name);
            //     }

            //     // Return combined HTML with flexbox alignment
            //     return '<div style="display: flex; align-items: center;">' .
            //                 $imageTag .
            //                 '<span style="margin-left: 10px;">' . $link . '</span>' .
            //         '</div>';
            // })



            // ->editColumn('category_id', function ($query) {
            //     return ($query->category_id != null && isset($query->category)) ? $query->category->name : '-';
            // })

            ->editColumn('category_id', function ($query) {
                // Get the associated category
                $category = $query->category;

                // Return the view and pass the category data
                return view('subcategory.category', compact('category'));
            })
            ->filterColumn('category_id', function ($query, $keyword) {
                $query->whereHas('category', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('category_id', function ($query, $order) {
                $query->join('categories', 'categories.id', '=', 'services.category_id')
                    ->orderBy('categories.name', $order);
            })
            ->editColumn('provider_id', function ($query) {
                return view('service.service', compact('query'));
            })
            ->filterColumn('provider_id', function ($query, $keyword) {
                $query->whereHas('providers', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->select('services.*')
                    ->join('users as providers', 'providers.id', '=', 'services.provider_id')
                    ->orderBy('providers.display_name', $order);
            })
            ->editColumn('price', function ($query) {
                return getPriceFormat($query->price) . '-' . ucfirst($query->type);
            })
            ->editColumn('discount', function ($query) {
                return $query->discount ? $query->discount . '%' : '-';
            })
            ->editColumn('service_request_status', function ($query) {
                $status = $query->service_request_status;
                $badgeText = __('messages.unknown');
                $badgeClass = 'badge-secondary';

                if ($status === "pending") {
                    $badgeText = __('messages.pending');
                    $badgeClass = 'badge badge-warning text-warning bg-warning-subtle';
                } elseif ($status === "reject") {
                    $badgeText = __('messages.reject');
                    $badgeClass = 'badge-danger';
                } elseif ($status === "approve") {
                    $badgeText = __('messages.approve');
                    $badgeClass = 'badge badge-active text-success bg-success-subtle';
                }

                return '<span class="badge ' . $badgeClass . '" id="datatable-row-' . $query->id . '">' . $badgeText . '</span>';
            })


            ->addColumn('action', function ($data) {
                $actionButtons = '';

                if ($data->trashed()) {
                    // Service is soft-deleted: Show restore and delete options
                    if (auth()->user()->hasAnyRole(['admin', 'provider'])) {
                        $restoreUrl = route('service.action', ['id' => $data->id, 'type' => 'restore']);
                        $forceDeleteUrl = route('service.action', ['id' => $data->id, 'type' => 'forcedelete']);

                        $actionButtons .= '
                            <a href="' . $restoreUrl . '" class="me-2 restore-btn"
                                title="' . __('messages.restore_form_title', ['form' => __('messages.service')]) . '"
                                data--submit="confirm_form"
                                data--confirmation="true"
                                data--ajax="true"
                                data-title="' . __('messages.restore_form_title', ['form' => __('messages.service')]) . '"
                                data-message="' . __('messages.restore_msg') . '"
                                data-datatable="reload">
                                <i class="fas fa-redo text-primary"></i>
                            </a>';

                        $actionButtons .= '
                            <a href="' . $forceDeleteUrl . '" class="me-2"
                                title="' . __('messages.forcedelete_form_title', ['form' => __('messages.service')]) . '"
                                data--submit="confirm_form"
                                data--confirmation="true"
                                data--ajax="true"
                                data-title="' . __('messages.forcedelete_form_title', ['form' => __('messages.service')]) . '"
                                data-message="' . __('messages.forcedelete_msg') . '"
                                data-datatable="reload">
                                <i class="far fa-trash-alt text-danger"></i>
                            </a>';
                    }
                } else {
                    // Service is not deleted
                    if ($data->service_request_status === "approve") {
                        // Show view (eye) icon for approved services
                        $actionButtons .= '<a href="' . route('service.create', ['id' => $data->id]) . '" class="btn btn-link p-0" title="' . __('messages.view') . '" data-bs-toggle="tooltip">
                            <i class="far fa-eye text-primary" ></i>
                        </a>';
                    } elseif ($data->service_request_status === "reject") {
                        // Show delete option for rejected services
                        $actionButtons .= '<a href="javascript:void(0);" class="trash-btn" data-id="' . $data->id . '" title="' . __('messages.delete') . '" data-bs-toggle="tooltip">
                            <i class="far fa-trash-alt text-danger"></i>
                        </a>';
                    } elseif ($data->service_request_status === "pending") {
                        // Show approve/reject buttons for pending services
                        if (auth()->user()->user_type === 'admin' || auth()->user()->user_type === 'demo_admin') {
                            $approveButton = '<button class="btn btn-link approve-btn py-0 px-1" data-id="' . $data->id . '" title="' . __('messages.approve') . '" data-bs-toggle="tooltip">
                                <i class="fas fa-check text-success"></i>
                            </button>';
                            $rejectButton = '<button class="btn btn-link reject-btn py-0 px-1" data-id="' . $data->id . '" title="' . __('messages.reject') . '" data-bs-toggle="tooltip">
                                <i class="fas fa-times text-danger"></i>
                            </button>';
                            $actionButtons .= '<div class="d-flex align-items-center gap-2">' . $approveButton . ' ' . $rejectButton . '</div>';
                        }
                    }
                }

                return '<div class="d-flex align-items-center">' . $actionButtons . '</div>';
            })
            ->rawColumns(['action', 'service_request_status', 'status', 'check', 'name'])
            ->toJson();
    }

    public function updateStatus(Request $request)
    {

        $serviceId = $request->id;
        $status = $request->status;
        $service = Service::find($serviceId);

        if ($service) {
            $service->service_request_status = ($status == 'approved') ? "approve" : "reject";
            $service->reject_reason = $request->reason;

            $service->save();

            $provider = User::find($service->provider_id);

            $activity_data = [
                'activity_type' => ($status == 'approved') ? 'service_request_approved' : 'service_request_reject',
                'service_id' => $service->id,
                'id' => $service->id,
                'provider_id' => $service->provider_id,
                'provider_name' => $provider->display_name ?? 'Unknown User',
                'user_name' => $provider?->display_name ?? 'Unknown User',
                'service_name' => $service->name,
                'reason' => $request->reason,
            ];
            $this->sendNotification($activity_data);

            return response()->json([
                'success' => true,
                'status' => $status,
                'serviceId' => $serviceId,
                'providerId' => $service->provider_id,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Service not found']);
    }


    public function providerServiceRequest(Request $request)
    {
        $auth_user = auth()->user();
        $filter = [
            'status' => 'Pending',
            'provider_id' => $auth_user->id,
        ];

        $pageTitle = __('messages.service_request', ['form' => __('messages.service')]);
        $assets = ['datatable'];

        $services = Service::where('provider_id', $auth_user->id)
            ->get();

        return view('service.provider-service-request', compact('pageTitle', 'auth_user', 'assets', 'services', 'filter'));
    }

    public function request_bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';


        switch ($actionType) {
            case 'change-status':
                if (in_array($request->status, ['pending', 'reject', 'approve'])) {
                    Service::whereIn('id', $ids)->update(['service_request_status' => $request->status]);
                    $message = __('messages.bulk_service_status_updated');
                } else {
                    return response()->json(['status' => false, 'message' => __('messages.invalid_status')]);
                }
                break;

            case 'delete':
                Service::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_service_deleted');
                break;

            case 'restore':
                // Check plan limits before restoring services
                if (default_earning_type() === 'subscription') {
                    // Get provider_id from the first service
                    $first_service = Service::withTrashed()->find($ids[0]);
                    if (!$first_service) {
                        return response()->json(['status' => false, 'message' => __('messages.service_not_found')]);
                    }
                    
                    $provider_id = $first_service->provider_id;
                    
                    // Get services to be restored
                    $services_to_restore = Service::withTrashed()
                        ->whereIn('id', $ids)
                        ->whereNotNull('deleted_at')
                        ->get();
                    
                    // Count current active services (not including soft-deleted ones)
                    $current_active_count = \App\Models\Service::where('provider_id', $provider_id)
                        ->where('status', 1)
                        ->count();
                    
                    // Get plan limit
                    $validation = validatePlanLimit($provider_id, 'service');
                    
                    if (!$validation['can_create']) {
                        return response()->json(['status' => false, 'message' => $validation['message']]);
                    }
                    
                    $limit = $validation['limit'];
                    
                    // Check if restoring would exceed limit
                    // After restore, total active services = current_active + services_to_restore
                    if ($limit !== 'unlimited' && ($current_active_count + $services_to_restore->count()) > $limit) {
                        $message = __('messages.service_restore_limit_exceeded', [
                            'limit' => $limit,
                            'current' => $current_active_count,
                            'restoring' => $services_to_restore->count()
                        ]);
                        return response()->json(['status' => false, 'message' => $message]);
                    }
                }
                
                Service::whereIn('id', $ids)->restore();
                $message = __('messages.bulk_service_restored');
                break;

            case 'permanently-delete':
                Service::whereIn('id', $ids)->forceDelete();
                $message = __('messages.bulk_service_permanently_deleted');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('messages.action_invalid')]);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }


    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';

     
        switch ($actionType) {
            case 'change-status':
                // If activating services, check plan limits
                // NOW ALSO CHECK FOR ADMINS - they should see the error too
                if ($request->status == 1 && default_earning_type() === 'subscription') {
                    // Get the provider_id - for admins, we need to get it from the request or the services
                    $provider_id = auth()->user()->id;
                    
                    // If admin is managing a specific provider's services, get that provider_id
                    if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
                        // Get provider_id from the first service
                        $first_service = Service::find($ids[0]);
                        if ($first_service) {
                            $provider_id = $first_service->provider_id;
                        }
                    }
                    
                   
                    // Get services to be activated
                    $services_to_activate = Service::whereIn('id', $ids)
                        ->where('status', 0)
                        ->get();

                  

                    // Activate services one by one, checking limit for each
                    $activated_count = 0;
                    $failed_services = [];
                    
                    foreach ($services_to_activate as $service) {
                        // Check if we can activate this service (excluding it from the count)
                        // Always check against 'service' type, not 'featured_service'
                        $can_activate = can_activate_resource($provider_id, 'service', $service->id);
                        
                        
                        
                        if ($can_activate) {
                            // Activate this service
                            $service->update(['status' => 1]);
                            $activated_count++;
                        } else {
                            // Cannot activate this service due to limit
                            $failed_services[] = $service->id;
                            
                          
                        }
                    }
                    
                    // Return appropriate message
                    if (count($failed_services) > 0) {
                        $limit = get_remaining_limit($provider_id, 'service');
                        if ($activated_count > 0) {
                            // Some services were activated successfully
                            $message = __('messages.bulk_service_partial_activation', [
                                'activated' => $activated_count,
                                'total' => count($services_to_activate),
                                'limit' => $limit
                            ]);
                            // Return success with informational message
                            return response()->json(['status' => true, 'message' => $message]);
                        } else {
                            // No services were activated
                            $message = __('messages.service_limit_exceeded', ['limit' => $limit, 'type' => __('messages.service')]);
                            return response()->json(['status' => false, 'message' => $message]);
                        }
                    }
                    // All services activated successfully, message will be set below
                } elseif ($request->status == 0 && default_earning_type() === 'subscription') {
                    // If deactivating, no need to check limits
                    Service::whereIn('id', $ids)->update(['status' => $request->status]);
                } else {
                    // For non-subscription, just update all
                    Service::whereIn('id', $ids)->update(['status' => $request->status]);
                }
                
                $message = 'Bulk Service Status Updated';
                break;

            case 'delete':
                Service::whereIn('id', $ids)->delete();
                $message = 'Bulk Service Deleted';
                break;

            case 'restore':
                Service::whereIn('id', $ids)->restore();
                $message = 'Bulk Service Restored';
                break;

            case 'permanently-delete':
                Service::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Service Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }



    /* user service list */
    public function getUserServiceList(Request $request)
    {

        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.service')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('service.user_service_list', compact('pageTitle', 'auth_user', 'assets', 'filter'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // dd($request->all());
        if (!auth()->user()->can('service add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;

        $auth_user = authSession();
        if (!$auth_user) {
            return redirect()->route('login')->withErrors(trans('messages.please_login'));
        }

        // Log session data for debugging
       
        // Enforce plan limits on page load
        if (default_earning_type() === 'subscription') {
            // For admins editing provider services, enforce limits for the service's provider
            if ($id && auth()->user()->hasRole(['admin', 'demo_admin'])) {
                $service = Service::find($id);
                if ($service && $service->provider_id) {
                    $this->enforcePlanLimits($service->provider_id);
                }
            } else {
                // For providers, enforce their own limits
                $this->enforcePlanLimits($auth_user->id);
            }
        }

        $language_array = $this->languagesArray();

        // Load service with relationships when editing
        $servicedata = null;
        if ($id) {
            $servicedata = Service::with([
                'category',
                'subcategory',
                'providers',
                'providerServiceAddress',
                'zones'
            ])->find($id);
        }

        // Get provider's mapped zones with necessary data
        $serviceZones = ProviderZoneMapping::with('zone')
            ->where('provider_id', $auth_user->id)
            ->get()
            ->map(function ($mapping) {
                return [
                    'id' => $mapping->zone->id,
                    'name' => $mapping->zone->name,
                    'provider_id' => $mapping->provider_id,
                    'zone_id' => $mapping->zone_id
                ];
            });

        // Get selected zones for the service if editing
        $selectedZones = [];
        if ($servicedata && $servicedata->zones) {
            $selectedZones = $servicedata->zones->pluck('id')->toArray();
        }

        $visittype = config('constant.VISIT_TYPE');

        $settingdata = Setting::where('type', '=', 'service-configurations')->first();

        $advancedPaymentSetting = 0;
        $slotservice = 0;
        $digital_services = 0;

        if ($settingdata) {
            $settings = json_decode($settingdata->value, true);
            $advancedPaymentSetting = $settings['advance_payment'];
            $slotservice = $settings['slot_service'];
            $digital_services = $settings['digital_services'];
        }

        if ($digital_services == 1) {
            $visittype = [
                'on_site' => 'On Site',
                'on_shop' => 'On Shop',
                'ONLINE' => 'Online',
            ];
        } else {
            $visittype = [
                'ON_SITE' => 'On Site',
            ];
        }

        $pageTitle = __('messages.update_form_title', ['form' => __('messages.service')]);

        if ($servicedata == null) {
            $pageTitle = __('messages.add_button_form', ['form' => __('messages.service')]);
            $servicedata = new Service;
            $services['is_service_request'] = 1;
        } else {
            if ($servicedata->provider_id !== auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
                return redirect(route('service.index'))->withErrors(trans('messages.demo_permission_denied'));
            }
        }

        $globalSeoSetting = \App\Models\SeoSetting::first();
        return view('service.create', compact('language_array', 'pageTitle', 'servicedata', 'auth_user', 'advancedPaymentSetting', 'visittype', 'slotservice', 'serviceZones', 'selectedZones', 'globalSeoSetting'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceRequest $request)
    {   
        \Log::info($request->all());
        if (demoUserPermission()) {
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        // Multi-language validation: Check if at least one language has required fields filled
        $language_option = sitesetupSession('get')->language_option ?? ["ar", "nl", "en", "fr", "de", "hi", "it"];
        $primary_locale = app()->getLocale() ?? 'en';
        
        // Check if at least one language has name filled
        $hasNameInAnyLanguage = false;
        
        // Check English name
        if (!empty($request->name)) {
            $hasNameInAnyLanguage = true;
        }
        
        // Check translations
        if (!$hasNameInAnyLanguage && $request->has('translations')) {
            $translations = $request->translations;
            foreach ($language_option as $lang) {
                if (isset($translations[$lang]['name']) && !empty(trim($translations[$lang]['name']))) {
                    $hasNameInAnyLanguage = true;
                    break;
                }
            }
        }
        
        // If no language has name filled, return error
        if (!$hasNameInAnyLanguage) {
            $message = __('messages.name_required_in_any_language');
            if ($request->is('api/*')) {
                return comman_message_response($message);
            } else {
                return redirect()->back()->withErrors(['name' => $message])->withInput();
            }
        }

        $services = $request->except('seo_image');
        
        // Determine provider_id early for validation
        $provider_id = !empty($request->provider_id) ? $request->provider_id : auth()->user()->id;
        
        // VALIDATION: Check provider type commission for service price
        // Get provider's provider type
        $provider = User::find($provider_id);
        if ($provider && $provider->provider_type_id) {
            $providerType = ProviderType::find($provider->provider_type_id);
            
            // If provider type exists and commission type is 'fixed'
            // Only validate if price type is NOT 'free'
            if ($providerType && $providerType->type === 'fixed' && $request->type !== 'free') {
                $servicePrice = (float) $request->price;
                $fixedCommission = (float) $providerType->commission;
                
                // Service price must be greater than fixed commission
                if ($servicePrice <= $fixedCommission) {
                    $message = __('messages.service_price_must_be_greater_than_commission', [
                        'commission' => $fixedCommission
                    ]);
                    
                    if ($request->is('api/*')) {
                        return comman_message_response($message);
                    } else {
                        return redirect()->back()
                            ->withErrors(['price' => $message])
                            ->withInput();
                    }
                }
            }
        }
        
        // VALIDATION: Check plan limits for NEW service creation
        if ($request->id == null && default_earning_type() === 'subscription' && !auth()->user()->hasRole('user')) {
            // Check if provider has active subscription
            $validation = validatePlanLimit($provider_id, 'service');
            
            if (!$validation['can_create']) {
                $message = $validation['message'];
                
                                
                if ($request->is('api/*')) {
                    return comman_message_response($message);
                } else {
                    return redirect()->back()->withErrors(['service' => $message])->withInput();
                }
            }
            
            // If service will be featured, also check featured service limit
            // IMPORTANT: Check the VALUE, not just if the key exists (Laravel Collective sends hidden field with 0)
            $will_be_featured = $request->input('is_featured', 0) == 1 ? 1 : 0;
            if ($will_be_featured == 1) {
                $featuredValidation = validatePlanLimit($provider_id, 'featured_service');
                
                if (!$featuredValidation['can_create']) {
                    $message = $featuredValidation['message'];
                    
                    \Log::info('Featured service creation blocked - plan limit validation failed', [
                        'provider_id' => $provider_id,
                        'validation' => $featuredValidation,
                        'message' => $message
                    ]);
                    
                    if ($request->is('api/*')) {
                        return comman_message_response($message);
                    } else {
                        return redirect()->back()->withErrors(['is_featured' => $message])->withInput();
                    }
                }
            }
        }
        
        // IMPORTANT: Check plan limits EARLY when editing service and changing status
        // This must happen BEFORE we do updateOrCreate
        if ($request->id && $request->id != null) {
            $existing_service = Service::find($request->id);
            
            if ($existing_service) {
                // Check if provider is being changed by admin
                $old_provider_id = $existing_service->provider_id;
                $new_provider_id = !empty($request->provider_id) ? $request->provider_id : $old_provider_id;
                
                // VALIDATION: Check if admin is changing provider and new provider has reached limit
                if ($old_provider_id != $new_provider_id && default_earning_type() === 'subscription') {
                    // Check if new provider has active subscription
                    $new_provider_has_subscription = is_any_plan_active($new_provider_id);
                    
                    if ($new_provider_has_subscription) {
                        // Check if new provider can accept this service
                        $validation = validatePlanLimit($new_provider_id, 'service');
                        
                        if (!$validation['can_create']) {
                            $message = __('messages.service_limit_reached_for_plan');
                            
                            \Log::info('Service provider change blocked - new provider limit reached', [
                                'service_id' => $request->id,
                                'old_provider_id' => $old_provider_id,
                                'new_provider_id' => $new_provider_id,
                                'validation' => $validation
                            ]);
                            
                            if ($request->is('api/*')) {
                                return comman_message_response($message);
                            } else {
                                return redirect()->back()
                                    ->withErrors(['provider_id' => $message])
                                    ->with('error', $message)
                                    ->withInput();
                            }
                        }
                        
                        // If service is featured, also check featured service limit for new provider
                        // IMPORTANT: Check the VALUE, not just if the key exists
                        $is_featured = $request->input('is_featured', 0) == 1 ? 1 : (int)$existing_service->is_featured;
                        if ($is_featured == 1) {
                            $featuredValidation = validatePlanLimit($new_provider_id, 'featured_service');
                            
                            if (!$featuredValidation['can_create']) {
                                $message = __('messages.featured_service_limit_reached_for_plan');
                                
                                \Log::info('Service provider change blocked - new provider featured limit reached', [
                                    'service_id' => $request->id,
                                    'old_provider_id' => $old_provider_id,
                                    'new_provider_id' => $new_provider_id,
                                    'validation' => $featuredValidation
                                ]);
                                
                                if ($request->is('api/*')) {
                                    return comman_message_response($message);
                                } else {
                                    return redirect()->back()
                                        ->withErrors(['provider_id' => $message])
                                        ->with('error', $message)
                                        ->withInput();
                                }
                            }
                        }
                    }
                }
                
                // Check if provider has subscription enabled
                // ONLY check subscription limits if earning type is 'subscription'
                $has_subscription = default_earning_type() === 'subscription' && is_any_plan_active($existing_service->provider_id);
                // IMPORTANT: Always use the service's existing provider_id, not from request
                // This ensures admins editing services also get limit checks
                $provider_id = $existing_service->provider_id;
                
                // Get the new status from request - ensure it's cast to int
                // IMPORTANT: Check if status is actually in the request (it might not be if form doesn't include it)
                $new_status = $request->has('status') ? (int)$request->status : (int)$existing_service->status;
                $existing_status = (int)$existing_service->status;
                
                // Get the new featured status
                // IMPORTANT: Check the VALUE, not just if the key exists (Laravel Collective sends hidden field with 0)
                $new_is_featured = $request->input('is_featured', 0) == 1 ? 1 : 0;
                $old_is_featured = (int)$existing_service->is_featured;
                
                $new_type = $new_is_featured == 1 ? 'featured_service' : 'service';
                $old_type = $old_is_featured == 1 ? 'featured_service' : 'service';
                
                \Log::info('Service edit - featured status check', [
                    'service_id' => $request->id,
                    'request_has_is_featured' => $request->has('is_featured'),
                    'new_is_featured' => $new_is_featured,
                    'old_is_featured' => $old_is_featured,
                    'new_type' => $new_type,
                    'old_type' => $old_type
                ]);
                
                \Log::info('Service edit - checking plan limits EARLY', [
                    'service_id' => $request->id,
                    'provider_id' => $provider_id,
                    'existing_status' => $existing_status,
                    'new_status' => $new_status,
                    'new_type' => $new_type,
                    'old_type' => $old_type,
                    'request_status' => $request->status ?? 'not set',
                    'request_status_type' => gettype($request->status ?? null),
                    'has_subscription' => $has_subscription,
                    'admin_editing' => auth()->user()->hasAnyRole(['admin', 'demo_admin']),
                    'user_id' => auth()->user()->id,
                    'user_roles' => auth()->user()->getRoleNames()->toArray()
                ]);
                
                // Case 1: Service is being activated (status 0 -> 1) - Check if provider has subscription
                if ($existing_status == 0 && $new_status == 1 && $has_subscription) {
                    // Use new validation function
                    $validation = validatePlanLimit($provider_id, 'service', $request->id, true);
                    
                    if (!$validation['can_create']) {
                        $message = $validation['message'];
                        
                        \Log::info('Service activation blocked EARLY - redirecting with error', [
                            'service_id' => $request->id,
                            'provider_id' => $provider_id,
                            'message' => $message,
                            'validation' => $validation,
                            'new_type' => $new_type,
                            'existing_status' => $existing_status,
                            'new_status' => $new_status
                        ]);
                        
                        if ($request->is('api/*')) {
                            return comman_message_response($message);
                        } else {
                            return redirect()->route('service.create', ['id' => $request->id])
                                ->withInput()
                                ->with('error', $message);
                        }
                    }
                    
                    // If service is featured, also check featured limit
                    if ($new_is_featured == 1) {
                        $featuredValidation = validatePlanLimit($provider_id, 'featured_service', $request->id, false);
                        
                        if (!$featuredValidation['can_create']) {
                            $message = $featuredValidation['message'];
                            
                            \Log::info('Featured service activation blocked', [
                                'service_id' => $request->id,
                                'provider_id' => $provider_id,
                                'message' => $message,
                                'validation' => $featuredValidation
                            ]);
                            
                            if ($request->is('api/*')) {
                                return comman_message_response($message);
                            } else {
                                return redirect()->route('service.create', ['id' => $request->id])
                                    ->withInput()
                                    ->with('error', $message);
                            }
                        }
                    }
                }
                
                // Case 2: Service type is changing (e.g., regular -> featured or featured -> regular)
                // ONLY check if service is currently active (status = 1) AND provider has subscription
                if ($old_type !== $new_type && $existing_status == 1 && $has_subscription) {
                    // If changing from regular to featured, check if featured service limit allows it
                    if ($old_type === 'service' && $new_type === 'featured_service') {
                        if (!can_activate_resource($provider_id, 'featured_service', $request->id)) {
                            $limit = get_remaining_limit($provider_id, 'featured_service');
                            $message = __('messages.featured_service_limit_exceeded', ['limit' => $limit]);
                            
                         
                            if ($request->is('api/*')) {
                                return comman_message_response($message);
                            } else {
                                return redirect()->route('service.create', ['id' => $request->id])
                                    ->withInput()
                                    ->with('error', $message);
                            }
                        }
                    }
                    // If changing from featured to regular, check if regular service limit allows it
                    elseif ($old_type === 'featured_service' && $new_type === 'service') {
                        if (!can_activate_resource($provider_id, 'service', $request->id)) {
                            $limit = get_remaining_limit($provider_id, 'service');
                            $message = __('messages.service_limit_exceeded', ['limit' => $limit, 'type' => __('messages.service')]);
                          
                            if ($request->is('api/*')) {
                                return comman_message_response($message);
                            } else {
                                return redirect()->route('service.create', ['id' => $request->id])
                                    ->withInput()
                                    ->with('error', $message);
                            }
                        }
                    }
                }
            }
        }
        
        // Handle SEO enabled/disabled state
        $services['seo_enabled'] = $request->has('seo_enabled') ? $request->seo_enabled : 0;
        if ($request->filled('meta_title')) {
            $services['slug'] = $request->has('meta_title') ? Str::slug($request->meta_title) : null;
        }
        $language_option = sitesetupSession('get')->language_option ?? ["ar", "nl", "en", "fr", "de", "hi", "it"];
        $defaultLanguage = getDefaultLanguage();
        $translatableAttributes = ['name', 'description','meta_title','meta_description','meta_keywords'];

        // Prepare data for main table
        $mainData = $request->except(array_merge(['seo_image', 'translations'], $translatableAttributes));
        
        // Main table always stores English. On UPDATE: preserve existing if English field is empty.
        foreach ($translatableAttributes as $attr) {
            $submitted = $request->input($attr, '');
            if ($request->id && trim($submitted) === '') {
                $existing = Service::find($request->id);
                $mainData[$attr] = $existing ? $existing->$attr : '';
            } else {
                $mainData[$attr] = $submitted;
            }
        }

        $primary_locale = app()->getLocale() ?? 'en';

        $services['service_type'] = !empty($request->service_type) ? $request->service_type : 'service';        $services['service_type'] = !empty($request->service_type) ? $request->service_type : 'service';
        $services['provider_id'] = !empty($request->provider_id) ? $request->provider_id : auth()->user()->id;
        if (auth()->user()->hasRole('user')) {
            $services['service_type'] = 'user_post_service';
        }



        if ($request->id == null && default_earning_type() === 'subscription' && !auth()->user()->hasRole('user')) {
            $exceed =  get_provider_plan_limit($services['provider_id'], 'service');
            if (!empty($exceed)) {
                if ($exceed == 1) {
                    $message = __('messages.limit_exceed', ['name' => __('messages.service')]);
                } else {
                    $message = __('messages.not_in_plan', ['name' => __('messages.service')]);
                }
                if ($request->is('api/*')) {
                    return comman_message_response($message);
                } else {
                    return  redirect()->back()->withErrors(['service' => $message])->withInput();
                }
            }
        }

        if ($request->id == null) {
            $services['added_by'] =  !empty($request->added_by) ? $request->added_by : auth()->user()->id;
            $services['is_service_request'] = 1;
            if (auth()->user()->hasRole('demo_admin') ||  auth()->user()->hasRole('admin')) {
                $services['service_request_status'] = 'approve';
                $services['is_service_request'] = 0;
            }
        }

        if ($request->id && $request->id != null) {
            $service_zone_id = ServiceZoneMapping::where('id', $request->id)->pluck('zone_id')->toArray();
            $service_zones = $request->service_zones;
            if (is_string($service_zones)) {
                $decoded = json_decode($service_zones, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $service_zones = $decoded;
                } elseif (strpos($service_zones, ',') !== false) {
                    $service_zones = explode(',', $service_zones);
                } else {
                    $service_zones = [$service_zones];
                }
            }
            $service_zones = array_filter(array_map('intval', (array) $service_zones));
            $removeZone = array_diff($service_zone_id, $service_zones);
            ServiceZoneMapping::where('service_id', $request->id)->whereIn('zone_id', $removeZone)->delete();
        }

        $services['provider_id'] = !empty($services['provider_id']) ?  $services['provider_id']     : auth()->user()->id;

        // Set is_featured, is_slot, is_enable_advance_payment BEFORE validation
        if (!$request->is('api/*')) {
            $services['is_featured'] = 0;
            $services['is_slot'] = 0;
            $services['is_enable_advance_payment'] = 0;
            if ($request->has('is_featured')) {
                $services['is_featured'] = 1;
            }
            if ($request->has('is_enable_advance_payment')) {
                $services['is_enable_advance_payment'] = 1;
            }
            if ($request->has('is_slot')) {
                $services['is_slot'] = 1;
            }
        } else {
            // For API requests, get is_featured from request and validate it
            $services['is_featured'] = $request->input('is_featured', 0);
            $services['is_slot'] = $request->input('is_slot', 0);
            $services['is_enable_advance_payment'] = $request->input('is_enable_advance_payment', 0);
            
            \Log::info('API Service Creation - is_featured value', [
                'is_featured' => $services['is_featured'],
                'provider_id' => $services['provider_id'],
                'request_data' => $request->only(['is_featured', 'name', 'id'])
            ]);
        }

        // Check plan limits for featured service when is_featured = 1
        // Check limit for BOTH creating NEW service AND editing existing service
        \Log::info('Service Store - Checking featured service', [
            'is_featured' => $services['is_featured'] ?? 'NOT SET',
            'provider_id' => $services['provider_id'],
            'earning_type' => default_earning_type(),
            'user_role' => auth()->user()->getRoleNames(),
            'request_is_api' => $request->is('api/*')
        ]);
        
        if (!empty($services['is_featured']) && $services['is_featured'] == 1 && default_earning_type() === 'subscription' && !auth()->user()->hasRole('user')) {
            \Log::info('Service Store - Featured service validation TRIGGERED');
            
            // Check featured service limit regardless of whether creating or editing
            // Pass the service ID when editing so it doesn't count itself
            $exceed = get_provider_plan_limit($services['provider_id'], 'featured_service', $request->id);
            
            \Log::info('Service Store - Validation result', [
                'exceed' => $exceed ?? 'NOT SET',
                'provider_id' => $services['provider_id'],
                'service_id' => $request->id ?? 'new',
                'exceed_is_set' => isset($exceed),
                'exceed_not_empty' => !empty($exceed)
            ]);
            
            // CRITICAL: Check if $exceed is set (not if it's empty)
            // $exceed can be 0 (not in plan) or 1 (limit exceeded), both should block
            if (isset($exceed) && $exceed !== '') {
                // $exceed = 1 means limit exceeded, $exceed = 0 means not in plan
                if ($exceed == 1) {
                    // Get the list of currently featured services to show in error message
                    $featuredServices = \App\Models\Service::where('provider_id', $services['provider_id'])
                        ->where('is_featured', 1)
                        ->pluck('name')
                        ->toArray();
                    
                    if (!empty($featuredServices)) {
                        $message = __('messages.featured_service_limit_exceeded_upgrade') . ' Currently featured: ' . implode(', ', $featuredServices);
                    } else {
                        $message = __('messages.featured_service_limit_exceeded_upgrade');
                    }
                } else {
                    $message = __('messages.not_in_plan', ['name' => __('messages.featured_service')]);
                }
                
                \Log::info('Featured service limit check - BLOCKING creation', [
                    'service_id' => $request->id ?? 'new',
                    'provider_id' => $services['provider_id'],
                    'is_featured' => $services['is_featured'],
                    'exceed_result' => $exceed,
                    'message' => $message
                ]);
                
                if ($request->is('api/*')) {
                    return comman_message_response($message);
                } else {
                    return redirect()->back()
                        ->withErrors(['is_featured' => $message])
                        ->with('error', $message)
                        ->withInput();
                }
            }
        }
        
        if (!empty($request->advance_payment_amount)) {
            $services['advance_payment_amount'] = $request->advance_payment_amount;
        }
        
        // Validate service price against provider type commission
        $provider = User::with('providertype')->find($services['provider_id']);
        if ($provider && $provider->providertype) {
            $providerType = $provider->providertype;
            
            // If commission type is "fixed", service price must be greater than commission
            // Only validate if price type is NOT 'free'
            if ($providerType->type === 'fixed' && !empty($request->price) && $request->type !== 'free') {
                $servicePrice = floatval($request->price);
                $commissionAmount = floatval($providerType->commission);
                
                if ($servicePrice <= $commissionAmount) {
                    $message = __('messages.service_price_must_exceed_commission', [
                        'amount' => currency_format($commissionAmount)
                    ]);
                    
                    if ($request->is('api/*')) {
                        return comman_message_response($message);
                    } else {
                        return redirect()->route('service.create', ['id' => $request->id])
                            ->withErrors(['price' => $message])
                            ->withInput();
                    }
                }
            }
        }
        
        // Merge mainData with services array
        $services = array_merge($services, $mainData);
        
        $result = Service::updateOrCreate(['id' => $request->id], $services);
        
        if ($request->is('api/*')) {
            if (isset($services['translations']) && is_string($services['translations'])) {
                $services['translations'] = json_decode($services['translations'], true);
            } else {
                $services['translations'] = $services['translations'] ?? [];
            }
        } else {
            $services['translations'] = $request->input('translations', []);
        }
        
        $result->saveTranslations($services, $translatableAttributes, $language_option, $primary_locale);
        
        if ($result->providerServiceAddress()->count() > 0) {
            $result->providerServiceAddress()->delete();
        }
        if ($request->provider_address_id != null) {
            foreach ($request->provider_address_id as $address) {
                $provider_service_address = [
                    'service_id'   => $result->id,
                    'provider_address_id'   => $address,
                ];
                $result->providerServiceAddress()->insert($provider_service_address);
            }
        }
        // Handle service zones
        if ($request->has('service_zones')) {
            try {
                $serviceZones = is_string($request->service_zones) ? json_decode($request->service_zones, true) : $request->service_zones;
                if (is_array($serviceZones)) {
                    $result->zones()->detach();
                    $providerZones = ProviderZoneMapping::where('provider_id', $services['provider_id'])
                        ->pluck('zone_id')
                        ->toArray();
                    $validZones = array_intersect($serviceZones, $providerZones);
                    if (!empty($validZones)) {
                        foreach ($validZones as $zoneId) {
                            ServiceZoneMapping::create([
                                'service_id' => $result->id,
                                'zone_id' => $zoneId,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error mapping service zones: ' . $e->getMessage(), [
                    'service_id' => $result->id,
                    'zones' => $serviceZones ?? [],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        if ($request->has('shop_ids')) {
            $shopIds = $request->input('shop_ids');
            if (is_string($shopIds)) {
                $shopIds = json_decode($shopIds, true) ?? explode(',', $shopIds);
            }
            $shopIds = array_filter(array_map('intval', (array) $shopIds));
            $result->shops()->sync($shopIds);
        }

        // Handle SEO image upload
        if ($request->hasFile('seo_image')) {
            storeMediaFile($result, $request->file('seo_image'), 'seo_image');
        }
        if ($request->is('api/*')) {
            $files = [];
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "service_attachment_" . $i;
                    if ($request->hasFile($attachment)) {
                        $files[] = $request->file($attachment);
                    }
                }
            }

            if (!empty($files)) {
                storeMediaFile($result, $files, 'service_attachment');
            }
        // } else {
        //     if ($request->hasFile('service_attachment')) {
        //         $file = [];

        //         foreach ($request->allFiles() as $key => $uploadedFile) {
        //             if (Str::startsWith($key, 'service_attachment_')) {
        //                 $file[] = $uploadedFile;
        //             }
        //         }

        //         storeMediaFile($result, $file, 'service_attachment');
        //     } elseif (!getMediaFileExit($result, 'service_attachment')) {
        //         return redirect()->route('service.create', ['id' => $result->id])
        //             ->withErrors(['service_attachment' => 'The attachments field is required.'])
        //             ->withInput();
        //     }
        // }

        } else {
            if ($request->hasFile('service_attachment')) {
                $files = $request->file('service_attachment');
                storeMediaFile($result, $files, 'service_attachment');
            } elseif (!getMediaFileExit($result, 'service_attachment')) {
                // If no file exists at all, return error
                return redirect()->route('service.create', ['id' => $result->id])
                    ->withErrors(['service_attachment' => 'The attachment field is required.'])
                    ->withInput();
            }
        }

        $message = __('messages.update_form', ['form' => __('messages.service')]);
        if ($result->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.service')]);
        }
        $activity_data = [
            'type' => 'service_request',
            'activity_type' => 'service_request',
            'activity_message' => __('messages.new_service_request', ['name' => $result->name ?? __('messages.service')]),
            'id' => $result->id,
            'service_id' => $result->id,
            'service_name' => $result->name,
            'provider_id' => $result->provider_id,
            'datetime' => now()->format('Y-m-d H:i:s'),
        ];
        $this->sendNotification($activity_data);
        if ($request->is('api/*')) {
            $response = [
                'message' => $message,
                'service_id' => $result->id
            ];
            return comman_custom_response($response);
        }
        return redirect(route('service.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        $service = \App\Models\Service::findOrFail($id);
        $globalSeoSetting = \App\Models\SeoSetting::first();

        // Fallback logic: use service meta if set, else global
        $metaTitle = $service->translate('meta_title', $locale) ?? $service->meta_title ?? $globalSeoSetting->meta_title ?? $service->name;
        $metaDescription = $service->translate('meta_description', $locale) ?? $service->meta_description ?? $globalSeoSetting->meta_description ?? '';
        $metaKeywords = $service->translate('meta_keywords', $locale) ?? $service->meta_keywords ?? $globalSeoSetting->meta_keywords ?? '';
        $slug = $service->translate('slug', $locale) ?? $service->slug ?? $globalSeoSetting->slug ?? '';
        // SEO image: try service's localized image, else global
        $seoImage = $service->getFirstMediaUrl('seo_image_' . $locale);
        if (empty($seoImage) && $globalSeoSetting) {
            $seoImage = $globalSeoSetting->getFirstMediaUrl('seo_image');
        }
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.service')] );
        return view('service.view', compact('service', 'metaTitle', 'metaDescription', 'metaKeywords', 'slug', 'seoImage', 'pageTitle', 'globalSeoSetting'));
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
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $service = Service::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.service')]);

        if ($service != '') {
            $service->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.service')]);
        }
        if (request()->is('api/*')) {
            return comman_custom_response(['message' => $msg, 'status' => true]);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
    public function action(Request $request)
    {
        $id = $request->id;
        $service = Service::withTrashed()->where('id', $id)->first();
        $msg = __('messages.not_found_entry', ['name' => __('messages.service')]);
        
        if ($request->type === 'restore') {
            // Check plan limits before restoring service
            if (default_earning_type() === 'subscription') {
                $provider_id = $service->provider_id;
                
                // Count current active services (not including soft-deleted ones)
                $current_active_count = \App\Models\Service::where('provider_id', $provider_id)
                    ->where('status', 1)
                    ->count();
                
                // Get plan limit
                $validation = validatePlanLimit($provider_id, 'service');
                
                if (!$validation['can_create']) {
                    return comman_custom_response(['message' => $validation['message'], 'status' => false]);
                }
                
                $limit = $validation['limit'];
                
                // Check if restoring would exceed limit
                // After restore, total active services = current_active + 1
                if ($limit !== 'unlimited' && ($current_active_count + 1) > $limit) {
                    $msg = __('messages.service_restore_limit_exceeded', [
                        'limit' => $limit,
                        'current' => $current_active_count,
                        'restoring' => 1
                    ]);
                    return comman_custom_response(['message' => $msg, 'status' => false]);
                }
            }
            
            $service->restore();
            $msg = __('messages.msg_restored', ['name' => __('messages.service')]);
        }

        if ($request->type === 'forcedelete') {
            $service->forceDelete();
            $msg = __('messages.msg_forcedelete', ['name' => __('messages.service')]);
        }

        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    public function saveFavouriteService(Request $request)
    {
        $user_favourite = $request->all();

        $result = UserFavouriteService::updateOrCreate(['id' => $request->id], $user_favourite);

        $message = __('messages.update_form', ['form' => __('messages.favourite')]);
        if ($result->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.favourite')]);
        }

        return  redirect()->back()->withSuccess($message);
    }

    public function deleteFavouriteService(Request $request)
    {

        $service_rating = UserFavouriteService::where('user_id', $request->user_id)->where('service_id', $request->service_id)->delete();

        $message = __('messages.delete_form', ['form' => __('messages.favourite')]);

        return  redirect()->back()->withSuccess($message);
    }

    public function getZonesByProvider($providerId)
    {
        $zones = ServiceZone::where('status', 1)
            ->whereHas('providers', function ($query) use ($providerId) {
                $query->where('users.id', $providerId);
            })
            ->whereNull('deleted_at')
            ->get(['id', 'name as text']);

        return response()->json($zones);
    }

    public function getServicesByZone($zoneId)
    {
        $services = Service::whereHas('zones', function ($query) use ($zoneId) {
            $query->where('service_zones.id', $zoneId);
        })
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->get();

        return response()->json($services);
    }

    public function getShops(Request $request)
    {
        $providerId = $request->provider_id;

        $shops = Shop::where('provider_id', $providerId)
            ->select('id', 'shop_name')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $shops
        ]);
    }

    /**
     * Enforce plan limits by deactivating services that exceed the limit
     */
    private function enforcePlanLimits($provider_id)
    {
        try {
            // Check if provider has an active plan
            if (is_any_plan_active($provider_id) != 1) {
                return; // No active plan
            }

            $active_plan = get_user_active_plan($provider_id);
            if (!$active_plan || $active_plan->plan_type !== 'limited') {
                return; // Unlimited plan
            }

            $plan_limitation = is_array($active_plan->plan_limitation) 
                ? $active_plan->plan_limitation 
                : json_decode($active_plan->plan_limitation, true) ?? [];

            // Check service limit
            if (isset($plan_limitation['service']) && $plan_limitation['service']['is_checked'] === 'on') {
                $service_limit = (int)$plan_limitation['service']['limit'];
                
                // Get active services count
                $active_services = Service::where('provider_id', $provider_id)
                    ->where('status', 1)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($active_services->count() > $service_limit) {
                    // Deactivate services beyond the limit (keep oldest, deactivate newest)
                    $services_to_deactivate = $active_services->slice($service_limit);
                    
                    foreach ($services_to_deactivate as $service) {
                        $service->update([
                            'status' => 0,
                            'is_featured' => 0,
                            'updated_at' => now()
                        ]);
                    }

                  
                }
            }

            // Check handyman limit
            if (isset($plan_limitation['handyman']) && $plan_limitation['handyman']['is_checked'] === 'on') {
                $handyman_limit = (int)$plan_limitation['handyman']['limit'];
                
                // Get active handymen count
                $active_handymen = User::where('provider_id', $provider_id)
                    ->where('status', 1)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($active_handymen->count() > $handyman_limit) {
                    // Deactivate handymen beyond the limit (keep oldest, deactivate newest)
                    $handymen_to_deactivate = $active_handymen->slice($handyman_limit);
                    
                    foreach ($handymen_to_deactivate as $handyman) {
                        $handyman->update([
                            'status' => 0,
                            'updated_at' => now()
                        ]);
                    }

                    
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error enforcing plan limits: ' . $e->getMessage(), [
                'provider_id' => $provider_id
            ]);
        }
    }
}
