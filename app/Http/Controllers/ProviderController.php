<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\ProviderSlotMapping;
use App\Http\Requests\UserRequest;
use App\Models\ProviderPayout;
use App\Models\ProviderSubscription;
use App\Models\PaymentGateway;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Hash;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\CommissionEarning;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AdminApproveEmail;
use App\Models\ServiceZone;
use App\Models\ServiceZoneMapping;
use App\Models\ProviderZoneMapping;
use App\Models\Service;
use App\Models\Shop;
use App\Services\ProviderSubscriptionDetailPresenter;
use App\Traits\NotificationTrait;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    use NotificationTrait;

    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.providers');
        if ($request->status === 'pending') {
            $pageTitle = __('messages.pending_list_form_title', ['form' => __('messages.provider')]);
        }
        if ($request->status === 'subscribe') {
            $pageTitle = __('messages.list_form_title', ['form' => __('messages.subscribe')]);
        }

        $auth_user = authSession();
        $assets = ['datatable'];
        $list_status = $request->status;
        $zone_id = $request->zone_id;
        return view('provider.index', compact('list_status', 'pageTitle', 'auth_user', 'assets', 'filter', 'zone_id'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        // For subscription list, query subscriptions directly instead of providers
        if ($request->list_status == 'subscribe') {
            $query = ProviderSubscription::query()->with(['provider', 'plan'])->orderBy('id', 'desc');
        } else {
            $query = User::query();
            $filter = $request->filter;

            if (isset($filter)) {
                if (isset($filter['column_status'])) {
                    $query->where('status', $filter['column_status']);
                }
            }
            $query = $query->where('user_type', 'provider');
            if (auth()->user()->hasAnyRole(['admin'])) {
                $query->withTrashed();
            }
            if ($request->list_status == 'pending') {
                $query = $query->where('status', 0);
            } else {
                $query = $query->where('status', 1);
            }
        }

        if ($request->zone_id != null && $request->list_status != 'subscribe') {
            $query = $query->whereHas('providerZones', function ($q) use ($request) {
                $q->where('zone_id', $request->zone_id);
            });
        }

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) use ($request) {
                if ($request->list_status == 'subscribe') {
                    return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="subscription" onclick="dataTableRowCheck(' . $row->id . ',this)">';
                } else {
                    return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="user" onclick="dataTableRowCheck(' . $row->id . ',this)">';
                }
            })
            ->editColumn('display_name', function ($row) use ($request) {
                if ($request->list_status == 'subscribe') {
                    $query = $row->provider;
                } else {
                    $query = $row;
                }
                return view('provider.user', compact('query'))->render();
            })
            ->editColumn('status', function ($row) use ($request) {
                if ($request->list_status == 'subscribe') {
                    $statusText = ucfirst($row->status);
                    if ($row->status == 'active') {
                        $badgeClass = 'badge-active text-success bg-success-subtle';
                    } elseif ($row->status == 'cancelled') {
                        $badgeClass = 'badge text-danger bg-danger-subtle';
                    } else {
                        $badgeClass = 'badge text-secondary bg-secondary-subtle';
                    }
                    return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
                } else {
                    if ($row->status == '0') {
                        $status = '<a class="btn-sm btn btn btn-success approve-btn"  href=' . route('provider.approve', $row->id) . '><i class="fa fa-check"></i>Approve</a>';
                    } else {
                        $status = '<span class="badge badge-active text-success bg-success-subtle">' . __('messages.active') . '</span>';
                    }
                    return $status;
                }
            });

        // Only add provider-specific columns for non-subscription queries
        if ($request->list_status != 'subscribe') {
            $datatable->editColumn('providertype_id', function ($query) {
                return ($query->providertype_id != null && isset($query->providertype)) ? $query->providertype->name : '-';
            })
            ->editColumn('address', function ($query) {
                return ($query->address != null && isset($query->address)) ? $query->address : '-';
            })
            ->editColumn('shop', function ($query) {
                $count = $query->shops()->count();
                $shop_count = '<h6 class="m-0"><a href="' . route('shop.index', ['provider_id' => $query->id]) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="View Shop Details">' . $count . '</a></h6>';
                return $shop_count;
            })
            ->editColumn('wallet', function ($query) {
                return view('provider.wallet', compact('query'));
            })
            ->editColumn('created_at', function ($query) {
                $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                $datetime = $sitesetup ? json_decode($sitesetup->value) : null;

                $formattedDate = optional($datetime)->date_format && optional($datetime)->time_format
                    ? date(optional($datetime)->date_format, strtotime($query->created_at)) . ' ' . date(optional($datetime)->time_format, strtotime($query->created_at))
                    : $query->created_at;

                return $formattedDate;
            })
            ->filterColumn('providertype_id', function ($query, $keyword) {
                $query->whereHas('providertype', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            });
        }

        // Add subscription-specific columns for subscribe list
        if ($request->list_status == 'subscribe') {
            $datatable->addColumn('plan', function ($row) {
                return $row->plan ? $row->plan->title : '-';
            })
            ->addColumn('type', function ($row) {
                return $row ? ucfirst($row->type) : '-';
            })
            ->addColumn('amount', function ($row) {
                return $row ? getPriceFormat(ceil($row->amount)) : '-';
            })
            ->addColumn('start_at', function ($row) {
                if ($row && $row->start_at) {
                    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                    $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                    $dateFormat = optional($datetime)->date_format ?? 'Y-m-d';
                    return date($dateFormat, strtotime($row->start_at));
                }
                return '-';
            })
            ->addColumn('end_at', function ($row) {
                if ($row && $row->end_at) {
                    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                    $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                    $dateFormat = optional($datetime)->date_format ?? 'Y-m-d';
                    return date($dateFormat, strtotime($row->end_at));
                }
                return '-';
            })
            ->addColumn('action', function ($row) {
                $subscriptionId = $row->id;
                $provider = $row->provider;
                return view('provider.subscription-action', compact('provider', 'subscriptionId'))->render();
            })
            ->filterColumn('display_name', function ($query, $keyword) {
                $query->whereHas('provider', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', '%' . $keyword . '%')
                      ->orWhere('last_name', 'like', '%' . $keyword . '%')
                      ->orWhere('email', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('plan', function ($query, $keyword) {
                $query->whereHas('plan', function ($q) use ($keyword) {
                    $q->where('title', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('type', function ($query, $keyword) {
                $query->where('type', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('amount', function ($query, $keyword) {
                $query->where('amount', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('start_at', function ($query, $keyword) {
                $query->whereDate('start_at', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('end_at', function ($query, $keyword) {
                $query->whereDate('end_at', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('status', function ($query, $keyword) {
                $query->where('status', 'like', '%' . $keyword . '%');
            });
        } else {
            $datatable->addColumn('action', function ($provider) {
                return view('provider.action', compact('provider'))->render();
            });
        }

        return $datatable->addIndexColumn()
            ->rawColumns(['check', 'display_name', 'wallet', 'action', 'status', 'shop'])
            ->toJson();
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        // Get IDs from datatable_ids array or rowIds parameter
        $ids = $request->input('datatable_ids', []);
        
        // Fallback to rowIds if datatable_ids is empty
        if (empty($ids) && $request->has('rowIds')) {
            $ids = explode(',', $request->rowIds);
        }
        
        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No items selected'], 400);
        }

        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';
        $listStatus = $request->list_status ?? null;

        switch ($actionType) {
            case 'change-status':
                if ($listStatus === 'subscribe') {
                    // Update subscription status
                    \App\Models\ProviderSubscription::whereIn('id', $ids)->update(['status' => $request->status]);
                    $message = 'Bulk Subscription Status Updated';
                } else {
                    // Update provider status
                    User::whereIn('id', $ids)->update(['status' => $request->status]);
                    $message = 'Bulk Provider Status Updated';
                }
                break;

            case 'delete':
                if ($listStatus === 'subscribe') {
                    \App\Models\ProviderSubscription::whereIn('id', $ids)->delete();
                    $message = 'Bulk Subscription Deleted';
                } else {
                    User::whereIn('id', $ids)->delete();
                    $message = 'Bulk Provider Deleted';
                }
                break;

            case 'restore':
                if ($listStatus !== 'subscribe') {
                    User::whereIn('id', $ids)->restore();
                    $message = 'Bulk Provider Restored';
                }
                break;

            case 'permanently-delete':
                if ($listStatus === 'subscribe') {
                    \App\Models\ProviderSubscription::whereIn('id', $ids)->forceDelete();
                    $message = 'Bulk Subscription Permanently Deleted';
                } else {
                    User::whereIn('id', $ids)->forceDelete();
                    $message = 'Bulk Provider Permanently Deleted';
                }
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

        if (!auth()->user()->can('provider add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $auth_user = authSession();

        $providerdata = User::find($id);
        $pageTitle = __('messages.update_form_title', ['form' => __('messages.provider')]);

        if ($providerdata == null) {
            $pageTitle = __('messages.add_button_form', ['form' => __('messages.provider')]);
            $providerdata = new User;
        }

        // Get all active service zones
        $serviceZones = ServiceZone::where('status', 1)->get();

        // Get selected zones for existing provider
        $selectedZones = [];
        if ($providerdata && $providerdata->id) {
            $selectedZones = $providerdata->serviceZones()->pluck('service_zones.id')->toArray();
        }

        $othersetting = Setting::where('type', 'OTHER_SETTING')->first();
        $nearby_provider = 0;
        if ($othersetting) {
            $decoded = json_decode($othersetting->value, true);
            $nearby_provider = $decoded['nearby_provider'] ?? 0;
        }

        return view('provider.create', compact('pageTitle', 'providerdata', 'auth_user', 'serviceZones', 'selectedZones', 'nearby_provider'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $loginuser = \Auth::user();
        if (demoUserPermission()) {
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();
        $id = $data['id'];
        $data['user_type'] = $data['user_type'] ?? 'provider';
        $data['is_featured'] = 0;

        if ($request->has('is_featured')) {
            $data['is_featured'] = 1;
        }

        $data['display_name'] = $data['first_name'] . " " . $data['last_name'];

        if ($id == null) {
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            $wallet = array(
                'title' => $user->display_name,
                'user_id' => $user->id,
                'amount' => 0
            );
            $result = Wallet::create($wallet);
        } else {
            $user = User::findOrFail($id);
            $user->fill($data)->update();
        }


        if ($request->id && $request->id != null) {

            $provider_zone = ProviderZoneMapping::where('provider_id', $request->id)->pluck('zone_id')->toArray();


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

            $removeZone = array_diff($provider_zone, $service_zones);

            $services = Service::where('provider_id', $request->id)->pluck('id')->toArray();

            ServiceZoneMapping::whereIn('service_id', $services)->whereIn('zone_id', $removeZone)->delete();
        }

        // Handle service zones
        if ($request->has('service_zones')) {
            $user->serviceZones()->sync($request->service_zones);
        } else {
            $user->serviceZones()->detach();
        }

        
      

        if ($user->wasRecentlyCreated) {
            $verificationLink = route('verify', ['id' => Crypt::encrypt($user->id)]);
            try {
                $this->sendNotification([
                    'activity_type'    => 'email_verification',
                    'user_id'          => $user->id,
                    'user_type'        => $user->user_type,
                    'user_name'        => $user->display_name,
                    'user_email'       => $user->email,
                    'verification_link' => $verificationLink,
                ]);
                $message = __('messages.email_verification_sent');
            } catch (\Throwable $e) {
                Log::error('Provider verification email failed: ' . $e->getMessage());
                $message = __('messages.email_send_failed_retry_later');
            }
        }
        // } elseif ($data['status'] == 1 && auth()->user()->hasAnyRole(['admin'])) {
        //     try {
        //         \Mail::send(
        //             'verification.verification_email',
        //             array(),
        //             function ($message) use ($user) {
        //                 $message->from(env('MAIL_FROM_ADDRESS'));
        //                 $message->to($user->email);
        //             }
        //         );
        //     } catch (\Throwable $e) {
        //         Log::error('Provider verification email failed: ' . $e->getMessage());
        //     }
        // }
        $user->assignRole($data['user_type']);
        storeMediaFile($user, $request->profile_image, 'profile_image');
        $message = __('messages.update_form', ['form' => __('messages.provider')]);
        if ($user->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.provider')]);
        }
        if ($user->providerTaxMapping()->count() > 0) {
            $user->providerTaxMapping()->delete();
        }
        if ($request->tax_id != null) {
            foreach ($request->tax_id as $tax) {
                $provider_tax = [
                    'provider_id'   => $user->id,
                    'tax_id'   => $tax,
                ];
                $user->providerTaxMapping()->insert($provider_tax);
            }
        }

        if ($request->is('api/*')) {
            return comman_message_response($message);
        }

        return redirect(route('provider.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $withdrawAmount = 0)
    {
        $auth_user = authSession();

        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $providerdata = User::with('providerDocument', 'booking', 'commission_earning')
            ->where('user_type', 'provider')
            ->where('id', $id)
            ->first();

        $data = Booking::where('provider_id', $id)->selectRaw(
            'COUNT(CASE WHEN status = "pending" THEN "pending" END) AS PendingStatusCount,
        COUNT(CASE WHEN status = "in_progress"  THEN "InProgress" END) AS InProgressstatuscount,
        COUNT(CASE WHEN status = "completed"  THEN "Completed" END) AS Completedstatuscount,
        COUNT(CASE WHEN status = "accept"  THEN "Accepted" END) AS Acceptedstatuscount,
        COUNT(CASE WHEN status = "on_going"  THEN "Ongoing" END) AS Ongoingstatuscount,
        COUNT(CASE WHEN status = "cancelled"  THEN "Cancelled" END) AS CancelledStatusCount'
        )->first()->toArray() ?? null;
        $totalbooking = Booking::where('provider_id', $id)->count();
        $providerPayout = ProviderPayout::where('provider_id', $id)->sum('amount') ?? 0;
        $commissionData = null;
        if ($providerdata !== null) {
            $commissionData = $providerdata->commission_earning()
                ->whereHas('getbooking', function ($query) {
                    $query->where('status', 'completed');
                })
                ->where('commission_status', 'unpaid')
                ->where('user_type', 'provider')
                ->pluck('booking_id');
            $ProviderEarning = 0;

            if ($commissionData->isNotEmpty()) {
                // Fetch all unpaid commissions for the relevant bookings in a single query
                $ProviderEarning = CommissionEarning::whereIn('booking_id', $commissionData)
                    ->whereIn('user_type', ['provider', 'handyman'])
                    ->where('commission_status', 'unpaid')
                    ->sum('commission_amount'); // Directly sum the commission_amount
            }
        } else {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }

        $commissionAmount = $ProviderEarning ? $ProviderEarning : 0;
        $alreadyWithdrawn = $providerPayout;
        $totalAmount = $alreadyWithdrawn + $commissionAmount;
        $wallet = $providerdata ? optional($providerdata->wallet)->amount : 0;

        $providerData = [
            'wallet' => $wallet,
            'providerAlreadyWithdrawAmt' => $alreadyWithdrawn,
            'pendWithdrwan' => $commissionAmount,
            'totalAmount' => $totalAmount,
            'total_booking' => $totalbooking,
        ];

        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);

        return view('provider.view', compact('pageTitle', 'providerdata', 'auth_user', 'data',  'providerPayout', 'providerData', 'totalAmount'));
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
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $provider = User::find($id);
        $msg = __('messages.msg_fail_to_delete', ['name' => __('messages.provider')]);

        if ($provider != '') {
            $provider->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.provider')]);
        }
        if (request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
    public function action(Request $request)
    {
        $id = $request->id;

        $provider  = User::withTrashed()->where('id', $id)->first();
        $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
        if ($request->type == 'restore') {
            $provider->restore();
            $msg = __('messages.msg_restored', ['name' => __('messages.provider')]);
        }

        if ($request->type === 'forcedelete') {
            $provider->forceDelete();
            $msg = __('messages.msg_forcedelete', ['name' => __('messages.provider')]);
        }
        if (request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
    public function bankDetails(ServiceDataTable $dataTable, Request $request)
    {
        $auth_user = authSession();
        $providerdata = User::with('getServiceRating')->where('user_type', 'provider')->where($request->id)->first();
        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return $dataTable
            ->with('provider_id', $request->id)
            ->render('provider.bank-details', compact('pageTitle', 'providerdata', 'auth_user'));
    }

    public function review(Request $request, $id)
    {
        $auth_user = authSession();
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $providerdata = User::with('getServiceRating')->where('user_type', 'provider')->where('id', $id)->first();
        $earningData = array();
        $time_zone = getTimeZone();

        foreach ($providerdata->getServiceRating as $bookingreview) {

            $booking_id = $bookingreview->id;
            // $date = optional($bookingreview->booking)->date ?? '-';
            $date = $bookingreview->updated_at->timezone($time_zone) ?? '-';
            $updated_at = $bookingreview->updated_at;
            $rating = $bookingreview->rating;
            $review = $bookingreview->review;
            $user_name = optional($bookingreview->customer)->first_name . ' ' . optional($bookingreview->customer)->last_name;
            $earningData[] = [
                'booking_id' => $booking_id,
                'date' => $date,
                'rating' => $rating,
                'review' => $review ?? '-',
                'user_name' => $user_name ?? '-',
                'updated_at' => date('Y-m-d H:i:s', strtotime($updated_at)),
            ];
        }

        if ($request->ajax()) {
            return Datatables::of($earningData)
                ->addIndexColumn()
                ->editColumn('date', function ($row) {
                    if (is_array($row)) {
                        $row = (object)$row;
                    }
                    $startAt = isset($row->date) ? $row->date : null;
                    if ($startAt !== null) {
                        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                        $datetime = $sitesetup ? json_decode($sitesetup->value) : null;

                        $date = optional($datetime)->date_format && optional($datetime)->time_format
                            ? date(optional($datetime)->date_format, strtotime($startAt)) . '  ' . date(optional($datetime)->time_format, strtotime($startAt))
                            : $startAt;
                        return $date;
                    }
                    return null;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return view('provider.review', compact('pageTitle', 'earningData', 'auth_user', 'providerdata'));
    }
    public function providerDetail(Request $request)
    {

        $tabpage = $request->tabpage;
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.service')]);
        $auth_user = authSession();
        $user_id = $auth_user->id;
        $user_data = User::find($user_id);
        $earningData = array();
        $payment_data = PaymentGateway::where('type', $tabpage)->first();
        $provideId = $request->providerId;
        $plandata = ProviderSubscription::where('user_id', $request->providerid)->orderBy('id', 'desc');
        if ($request->tabpage == 'subscribe-plan') {
            $plandata = $plandata->where('plan_type', 'subscribe');
        }
        if ($request->tabpage == 'unsubscribe-plan') {
            $plandata = $plandata->where('plan_type', 'unsubscribe');
        }
        switch ($tabpage) {
            case 'all-plan':

                if ($request->ajax() && $request->type == 'tbl') {
                    return  Datatables::of($plandata)
                        ->addColumn('provider_name', function ($row) {
                            if ($row->provider) {
                                return $row->provider->first_name . ' ' . $row->provider->last_name;
                            }
                            return '-';
                        })
                        ->addIndexColumn()
                        ->rawColumns([])
                        ->make(true);
                }

                return view('providerdetail.all-plan', compact('user_data', 'earningData', 'tabpage', 'auth_user', 'payment_data', 'provideId'));
                break;
            case 'subscribe-plan':
                if ($request->ajax() && $request->type == 'tbl') {
                    return  Datatables::of($plandata)
                        ->addIndexColumn()
                        ->rawColumns([])
                        ->make(true);
                }
                return view('providerdetail.subscribe-plan', compact('user_data', 'earningData', 'tabpage', 'auth_user', 'payment_data', 'provideId'));

                break;
            case 'unsubscribe-plan':
                if ($request->ajax() && $request->type == 'tbl') {
                    return  Datatables::of($plandata)
                        ->addIndexColumn()
                        ->rawColumns([])
                        ->make(true);
                }
                return view('providerdetail.unsubscribe-plan', compact('user_data', 'earningData', 'tabpage', 'auth_user', 'payment_data', 'provideId'));

                break;
            default:
                $data  = view('providerdetail.' . $tabpage, compact('tabpage', 'auth_user', 'payment_data'))->render();
                break;
        }

        return response()->json($data);
    }

public function providerSubscription(Request $request, $id)
{
    $auth_user = authSession();

    // Check if the current user is authorized to view this subscription
    if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
        return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
    }

    // Default tab page for the provider
    $tabpage = 'all-plan';

    // Fetch the provider's data, including the provider's documents
    $providerdata = User::with('providerDocument')->where('user_type', 'provider')->where('id', $id)->first();

    // If no provider data is found, return with an error
    if (empty($providerdata)) {
        $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
        return redirect(route('provider.index'))->withError($msg);
    }

    // Apply the search filter if present
    $searchValue = $request->get('search_value');

    // Initialize query for provider subscriptions
    $query = ProviderSubscription::with('provider')->where('user_id', $id);

    // Apply search filter
    if ($searchValue) {
        // If the search value is numeric, filter by ID
        if (is_numeric($searchValue)) {
            $query->where('id', $searchValue);
        } else {
            // Otherwise, filter by provider name (assuming it's a string)
            $query->whereHas('provider', function ($q) use ($searchValue) {
                $q->where('display_name', 'like', '%' . $searchValue . '%');
            });
        }
    }

    // Fetch provider subscription data
    $subscriptions = $query->get();

    // Process and return data for DataTables
    $subscriptionData = [];
    foreach ($subscriptions as $subscription) {
        $subscriptionData[] = [
            'id' => $subscription->id,
            'provider_name' => $subscription->provider->display_name,
            'title' => $subscription->title,
            'type' => $subscription->type,
            'amount' => $subscription->amount,
            'start_at' => $subscription->start_at,
            'end_at' => $subscription->end_at,
            'status' => $subscription->status,
        ];
    }

    // If the request is AJAX, return the data for DataTables
    if ($request->ajax()) {
        return Datatables::of($subscriptionData)
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                return $row['status'] === 'active'
                    ? '<span class="badge badge-active text-success bg-success-subtle">Active</span>'
                    : '<span class="badge badge-inactive text-danger bg-danger-subtle">Inactive</span>';
            })
            ->editColumn('amount', function ($row) {
                return getPriceFormat($row['amount']);
            })
            ->editColumn('start_at', function ($row) {
                // Fetch site setup settings
                $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                $datetime = $sitesetup ? json_decode($sitesetup->value) : null;

                // Format start_at if date and time formats are available
                if ($row['start_at']) {
                    $formattedDate = optional($datetime)->date_format && optional($datetime)->time_format
                        ? date(optional($datetime)->date_format, strtotime($row['start_at'])) . ' ' . date(optional($datetime)->time_format, strtotime($row['start_at']))
                        : Carbon::parse($row['start_at'])->format('Y-m-d'); // Default format if settings are not available
                    return $formattedDate;
                }

                return '-';
            })
            ->editColumn('end_at', function ($row) {
                // Fetch site setup settings
                $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                $datetime = $sitesetup ? json_decode($sitesetup->value) : null;

                // Format end_at if date and time formats are available
                if ($row['end_at']) {
                    $formattedDate = optional($datetime)->date_format && optional($datetime)->time_format
                        ? date(optional($datetime)->date_format, strtotime($row['end_at'])) . ' ' . date(optional($datetime)->time_format, strtotime($row['end_at']))
                        : Carbon::parse($row['end_at'])->format('Y-m-d'); // Default format if settings are not available
                    return $formattedDate;
                }

                return '-';
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    // Page Title
    $pageTitle = __('messages.plan');

    // Return the view
    return view('service.view', compact('pageTitle', 'providerdata', 'auth_user', 'tabpage', 'id'));
}



    public function approve($id)
    {
        try {
            // Log the approval attempt for debugging
            \Log::info('Provider approval attempt started', ['provider_id' => $id]);

            $provider = User::find($id);

            if (!$provider) {
                \Log::warning('Provider not found for approval', ['provider_id' => $id]);
                $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
                if (request()->ajax()) {
                    return response()->json(['status' => false, 'message' => $msg], 404);
                }
                return redirect()->back()->withError($msg);
            }

            // Check if provider is already approved
            if ($provider->status == 1) {
                $msg = 'Provider is already approved';
                \Log::info('Provider already approved', ['provider_id' => $id]);
                if (request()->ajax()) {
                    return response()->json(['status' => false, 'message' => $msg]);
                }
                return redirect()->back()->withError($msg);
            }

            // Update provider status
            $provider->status = 1;
            $provider->save();

            \Log::info('Provider status updated successfully', ['provider_id' => $id]);

            // Try to send email, but don't fail the approval if email fails
            try {
                $verificationLink = route('verify', ['id' => Crypt::encrypt($provider->id)]);
                \Log::info('Attempting to send approval email', [
                    'provider_id' => $id,
                    'email' => $provider->email,
                    'verification_link' => $verificationLink
                ]);

                Mail::to($provider->email)->send(new AdminApproveEmail($verificationLink));
                \Log::info('Approval email sent successfully', ['provider_id' => $id]);

            } catch (\Exception $emailError) {
                \Log::error('Failed to send approval email (but approval succeeded): ' . $emailError->getMessage());
                // Continue with success since the main approval worked
            }

            $msg = __('messages.approve_successfully');
            \Log::info('Provider approval completed successfully', ['provider_id' => $id]);

            if (request()->ajax()) {
                return response()->json(['status' => true, 'message' => $msg]);
            }

            return redirect()->back()->withSuccess($msg);

        } catch (\Exception $e) {
            // Log the detailed error for debugging
            \Log::error('Provider approval failed with exception', [
                'provider_id' => $id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]);

            // Return specific error message instead of generic one
            $msg = 'Approval failed: ' . $e->getMessage();

            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $msg], 500);
            }

            return redirect()->back()->withError($msg);
        }
    }

    public function getChangePassword(Request $request)
    {
        $id = $request->id;
        $auth_user = authSession();

        $providerdata = User::find($id);
        $pageTitle = __('messages.change_password', ['form' => __('messages.change_password')]);
        return view('provider.changepassword', compact('pageTitle', 'providerdata', 'auth_user'));
    }

    public function changePassword(Request $request)
    {
        if (demoUserPermission()) {
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $user = User::where('id', $request->id)->first();

        if ($user == "") {
            $message = __('messages.user_not_found');
            return comman_message_response($message, 400);
        }

        $validator = \Validator::make($request->all(), [
            'old' => 'required|min:8|max:255',
            'password' => 'required|min:8|confirmed|max:255',
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('password')) {
                $message = __('messages.confirmed', ['name' => __('messages.password')]);
                return redirect()->route('provider.changepassword', ['id' => $user->id])->with('error', $message);
            }
            return redirect()->route('provider.changepassword', ['id' => $user->id])->with('errors', $validator->errors());
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old, $hashedPassword);

        $same_exits = Hash::check($request->password, $hashedPassword);
        if ($match) {
            if ($same_exits) {
                $message = __('messages.old_new_pass_same');
                return redirect()->route('provider.changepassword', ['id' => $user->id])->with('error', $message);
            }

            $user->fill([
                'password' => Hash::make($request->password)
            ])->save();
            $message = __('messages.password_change');
            return redirect()->route('provider.index')->withSuccess($message);
        } else {
            $message = __('messages.valid_password');
            return redirect()->route('provider.changepassword', ['id' => $user->id])->with('error', $message);
        }
    }
    public function getProviderTimeSlot(Request $request)
    {
        $auth_user = authSession();
        $id = $request->id;
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $providerdata = User::with('providerslotsmapping')->where('user_type', 'provider')->where('id', $id)->first();
        date_default_timezone_set($admin->time_zone ?? 'UTC');

        $current_time = \Carbon\Carbon::now();
        $time = $current_time->toTimeString();

        $current_day = strtolower(date('D'));

        $provider_id = $request->id ?? auth()->user()->id;

        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        $slotsArray = ['days' => $days];
        $activeDay = 'mon';
        foreach ($days as $value) {
            $slot = ProviderSlotMapping::where('provider_id', $provider_id)
                ->where('days', $value)
                ->orderBy('start_at', 'asc')
                ->pluck('start_at')
                ->toArray();

            $obj = [
                "day" => $value,
                "slot" => $slot,
            ];
            $slotsArray[] = $obj;
        }

        $pageTitle = __('messages.slot', ['form' => __('messages.slot')]);
        return view('provider.timeslot', compact('auth_user', 'slotsArray', 'pageTitle', 'activeDay', 'provider_id', 'providerdata'));
    }

    public function editProviderTimeSlot(Request $request)
    {
        $auth_user = authSession();
        $id = $request->id;
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('provider.time-slot', auth()->user()->id))->withErrors(trans('messages.demo_permission_denied'));
        }
        $providerdata = User::with('providerslotsmapping')->where('user_type', 'provider')->where('id', $id)->first();
        date_default_timezone_set($admin->time_zone ?? 'UTC');

        $current_time = \Carbon\Carbon::now();
        $time = $current_time->toTimeString();

        $current_day = strtolower(date('D'));

        $provider_id = $request->id ?? auth()->user()->id;

        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        $slotsArray = ['days' => $days];
        $activeDay = 'mon';
        $activeSlots = [];

        foreach ($days as $value) {
            $slot = ProviderSlotMapping::where('provider_id', $provider_id)
                ->where('days', $value)
                ->orderBy('start_at', 'asc')
                ->selectRaw("SUBSTRING(start_at, 1, 5) as start_at")
                ->pluck('start_at')
                ->toArray();

            $obj = [
                "day" => $value,
                "slot" => $slot,
            ];
            $slotsArray[] = $obj;
            $activeSlots[$value] = $slot;
        }
        $pageTitle = __('messages.slot', ['form' => __('messages.slot')]);

        return view('provider.edittimeslot', compact('auth_user', 'slotsArray', 'pageTitle', 'activeDay', 'provider_id', 'activeSlots', 'providerdata'));
    }

    public function getProviderShop(Request $request, $id)
    {
        $auth_user = authSession();

        // Permission check
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))
                ->withErrors(trans('messages.demo_permission_denied'));
        }

        // Provider details
        $providerdata = User::with('providerbank')->findOrFail($id);

        // If AJAX → return DataTable JSON
        if ($request->ajax()) {
            $query = Shop::with(['country', 'state', 'city', 'provider'])
                ->withTrashed()
                ->where('provider_id', $id);


            $filter = $request->filter;

            if (isset($filter['column_status']) && $filter['column_status'] !== '') {
                $query->where('is_active', $filter['column_status']);
            }

            return DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    $search = $request->input('search.value');

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->where('shop_name', 'like', "%{$search}%")
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
                    $shopHtml = '
                    <div class="d-flex gap-3 align-items-center">
                        <img src="' . getSingleMedia($shop, 'shop_attachment', null) . '" alt="service" class="avatar avatar-40 rounded-pill">
                        <div class="text-start">
                            <h6 class="m-0">' . e($shop->shop_name) . '</h6>
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

        // Non-AJAX → return view
        return view('provider.shop', compact('id', 'providerdata'));
    }

    /**
     * Get subscription data for provider list page
     */
    public function getSubscriptionData(Request $request)
    {
        try {
            $subscriptions = ProviderSubscription::with('provider')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();

            $data = $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'provider_name' => optional($subscription->provider)->display_name ?? 'N/A',
                    'title' => $subscription->title,
                    'type' => $subscription->type,
                    'amount' => $subscription->amount,
                    'amount_formatted' => getPriceFormat($subscription->amount),
                    'start_at' => $subscription->start_at,
                    'start_at_formatted' => $subscription->start_at ? date('M d, Y g:i A', strtotime($subscription->start_at)) : '-',
                    'end_at' => $subscription->end_at,
                    'end_at_formatted' => $subscription->end_at ? date('M d, Y g:i A', strtotime($subscription->end_at)) : '-',
                    'status' => $subscription->status,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error loading subscription data: ' . $e->getMessage()
            ], 500);
        }
    }
    public function subscriptionDetail($subscriptionId)
    {
        try {
            $subscription = ProviderSubscription::with('provider', 'plan')->findOrFail($subscriptionId);
            
            // Check if provider is viewing their own subscription
            $auth_user = authSession();
            if ($auth_user->hasRole('provider') && $subscription->user_id !== $auth_user->id) {
                abort(403, 'Unauthorized access');
            }
            
            $viewData = app(ProviderSubscriptionDetailPresenter::class)->build($subscription);
            
            return view('provider.subscription-detail', $viewData);
        } catch (ModelNotFoundException $e) {
            abort(404, 'Subscription not found');
        }
    }

    public function downloadSubscriptionInvoice($encryptedId)
    {
        try {
            // Decrypt the subscription ID
            try {
                $subscriptionId = decrypt($encryptedId);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                \Log::warning('Invalid encrypted subscription ID', [
                    'encrypted_id' => $encryptedId,
                    'user_id' => auth()->id()
                ]);
                abort(404, 'Invalid invoice link');
            }
            
            $subscription = ProviderSubscription::with(['provider', 'plan', 'payment'])->findOrFail($subscriptionId);
            
            // Security checks
            $auth_user = authSession();
            
            // Admin can download any invoice
            if ($auth_user->hasRole('admin') || $auth_user->hasRole('demo_admin')) {
                // Admin access allowed
            } 
            // Provider can only download their own invoices
            elseif ($auth_user->hasRole('provider')) {
                if ($subscription->user_id !== $auth_user->id) {
                    \Log::warning('Unauthorized invoice access attempt', [
                        'user_id' => $auth_user->id,
                        'subscription_id' => $subscriptionId,
                        'subscription_owner' => $subscription->user_id
                    ]);
                    abort(403, 'Unauthorized access to this invoice');
                }
            } 
            // Other roles not allowed
            else {
                abort(403, 'You do not have permission to download invoices');
            }
            
            // Set locale based on user's language preference
            // Exception: Hindi is not supported due to DejaVu Sans font limitations
            // Hindi will fallback to English to avoid boxes in PDF
            $locale = 'en'; // Default fallback
            
            // Get user's language preference
            if (auth()->check()) {
                $userLanguage = auth()->user()->language_option ?? null;
                if ($userLanguage && !empty($userLanguage)) {
                    $locale = $userLanguage;
                }
                // Fallback to session locale
                elseif (session()->has('locale')) {
                    $locale = session()->get('locale');
                }
                // Fallback to DEFAULT_LANGUAGE from .env
                else {
                    $locale = env('DEFAULT_LANGUAGE', 'en');
                }
            }
            
            // Force English for Hindi to avoid rendering issues
            if ($locale === 'hi') {
                $locale = 'en';
            }
            
            app()->setLocale($locale);
            
            // Generate PDF
            $pdf = \PDF::loadView('provider.subscription-invoice-pdf', [
                'subscription' => $subscription
            ]);
            
            // Log successful download
            \Log::info('Invoice downloaded', [
                'user_id' => $auth_user->id,
                'subscription_id' => $subscriptionId,
                'user_role' => $auth_user->roles->pluck('name')->first()
            ]);
            
            return $pdf->download('subscription_invoice_' . $subscription->id . '.pdf');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Invoice not found', [
                'encrypted_id' => $encryptedId,
                'user_id' => auth()->id()
            ]);
            abort(404, 'Subscription not found');
        } catch (\Exception $e) {
            \Log::error('Invoice download error', [
                'encrypted_id' => $encryptedId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error generating invoice');
        }
    }

    public function myBilling(Request $request)
    {
        $auth_user = authSession();
        // Only providers can access this page
        if ($auth_user->user_type !== 'provider') {
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }

        // Check if earning type is subscription
        if (default_earning_type() !== 'subscription') {
            return redirect(route('home'))->withErrors('This feature is only available for subscription-based providers');
        }

        $pageTitle = __('messages.my_billing');
        $assets = ['datatable'];

        return view('provider.my-billing', compact('pageTitle', 'auth_user', 'assets'));
    }

    public function myBillingData(DataTables $datatable, Request $request)
    {
        $auth_user = authSession();
        
        $query = ProviderSubscription::with(['plan', 'payment'])
            ->where('user_id', $auth_user->id)
            ->orderBy('id', 'desc');

        return $datatable->eloquent($query)
            ->addIndexColumn()
            ->editColumn('title', function ($row) {
                return $row->plan ? $row->plan->title : '-';
            })
            ->editColumn('type', function ($row) {
                return ucfirst($row->type);
            })
            ->editColumn('amount', function ($row) {
                return getPriceFormat(ceil($row->amount));
            })
            ->editColumn('start_at', function ($row) {
                if ($row->start_at) {
                    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                    $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                    $dateFormat = optional($datetime)->date_format ?? 'Y-m-d';
                    return date($dateFormat, strtotime($row->start_at));
                }
                return '-';
            })
            ->editColumn('end_at', function ($row) {
                if ($row->end_at) {
                    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                    $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                    $dateFormat = optional($datetime)->date_format ?? 'Y-m-d';
                    return date($dateFormat, strtotime($row->end_at));
                }
                return '-';
            })
            ->editColumn('status', function ($row) {
                $statusText = ucfirst($row->status);
                if ($row->status == 'active') {
                    $badgeClass = 'badge-active text-success bg-success-subtle';
                } elseif ($row->status == 'cancelled') {
                    $badgeClass = 'badge text-danger bg-danger-subtle';
                } else {
                    $badgeClass = 'badge text-secondary bg-secondary-subtle';
                }
                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->editColumn('payment_status', function ($row) {
                if ($row->payment && $row->payment->payment_status) {
                    $paymentStatus = $row->payment->payment_status;
                    if ($paymentStatus == 'paid') {
                        $badgeClass = 'badge text-success bg-success-subtle';
                    } else {
                        $badgeClass = 'badge text-warning bg-warning-subtle';
                    }
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($paymentStatus) . '</span>';
                }
                return '<span class="badge text-secondary bg-secondary-subtle">-</span>';
            })
            ->addColumn('action', function ($row) {
                $viewUrl = route('provider.subscription-detail', $row->id);
                $invoiceUrl = route('provider-subscription.download-invoice', encrypt($row->id));
                $hasPayment = $row->payment && $row->payment->payment_status === 'paid';
                
                return view('provider.my-billing-action', compact('row', 'viewUrl', 'invoiceUrl', 'hasPayment'))->render();
            })
            ->filterColumn('title', function ($query, $keyword) {
                $query->whereHas('plan', function ($q) use ($keyword) {
                    $q->where('title', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('type', function ($query, $keyword) {
                $query->where('type', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('amount', function ($query, $keyword) {
                $query->where('amount', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('start_at', function ($query, $keyword) {
                $query->whereDate('start_at', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('end_at', function ($query, $keyword) {
                $query->whereDate('end_at', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('status', function ($query, $keyword) {
                $query->where('status', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('payment_status', function ($query, $keyword) {
                $query->whereHas('payment', function ($q) use ($keyword) {
                    $q->where('payment_status', 'like', '%' . $keyword . '%');
                });
            })
            ->rawColumns(['status', 'payment_status', 'action'])
            ->toJson();
    }
}

