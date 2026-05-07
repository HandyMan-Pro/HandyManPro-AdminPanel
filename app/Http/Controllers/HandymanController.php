<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Yajra\DataTables\DataTables;
use Hash;
use App\Models\Setting;
use App\Models\Booking;
use App\Models\BookingHandymanMapping;
use App\Models\HandymanPayout;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use App\Traits\NotificationTrait;
use Illuminate\Support\Facades\Crypt;

class HandymanController extends Controller
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
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.handyman')]);
        if ($request->status == 'pending') {
            $pageTitle = __('messages.pending_list_form_title', ['form' => __('messages.handyman')]);
        }
        if ($request->status == 'unassigned') {
            $pageTitle = __('messages.unassigned_list_form_title', ['form' => __('messages.handyman')]);
        }
        if ($request->status == 'request') {
            $pageTitle = __('messages.pending_list_form_title', ['form' => __('messages.handyman')]);
        }
        $auth_user = authSession();
        $assets = ['datatable'];
        $list_status = $request->status;
        return view('handyman.index', compact('list_status', 'pageTitle', 'auth_user', 'assets', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = User::where('user_type', 'handyman');

        $filter = $request->filter;

        // Apply filters
        if (!empty($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }

        // Include trashed users based on roles
        if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('provider')) {
            $query->withTrashed();
        }

        // Apply provider-specific filters
        if (auth()->user()->hasRole('provider')) {
            $query->where('provider_id', auth()->user()->id);
        }

        // Apply list status filters
        switch ($request->list_status) {
            case 'pending':
                $query->where('status', 0);
                break;
            case 'unassigned':
                $query->where('status', 1)->whereNull('provider_id');
                break;
            case 'request':
                $query->where('status', 0)->where(function ($query) {
                    $query->whereNull('provider_id')->orWhereNotNull('provider_id');
                });
                break;
            default:
                $query->where('status', 1)->whereNotNull('provider_id');
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $row->id . '" name="datatable_ids[]" value="' . $row->id . '" data-type="user" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('display_name', fn($query) => view('handyman.user', compact('query')))
            ->editColumn('address', fn($query) => $query->address ?? '-')
            ->editColumn('created_at', function ($query) {
                $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                $datetime = $sitesetup ? json_decode($sitesetup->value) : null;

                return $datetime && $datetime->date_format && $datetime->time_format
                    ? date($datetime->date_format, strtotime($query->created_at)) .'  '. date($datetime->time_format, strtotime($query->created_at))
                    : $query->created_at;
            })
            ->editColumn('status', function ($query) {
                return $query->status == 0
                    ? '<a class="btn-sm text-white btn btn-success" href=' . route('handyman.approve', $query->id) . '>Accept</a>'
                    : '<span class="badge badge-active badge badge-active text-success bg-success-subtle">' . __('messages.active') . '</span>';
            })
            ->editColumn('provider_id', fn($query) => view('handyman.provider', compact('query')))
            ->filterColumn('provider_id', function ($qry, $keyword) {
                $qry->whereHas('providers', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%');
                });
            })
            ->orderColumn('provider_id', function ($query, $order) {
                $query->selectRaw('users.*, (SELECT display_name FROM users AS providers WHERE providers.id = users.provider_id) AS provider_display_name')
                      ->orderBy('provider_display_name', $order);
            })
            ->editColumn('wallet', function ($query){
                return view('handyman.wallet', compact('query'));
            })
            ->addColumn('action', fn($handyman) => view('handyman.action', compact('handyman'))->render())
            ->addIndexColumn()
            ->rawColumns(['check', 'display_name', 'action', 'status','wallet'])
            ->toJson();
    }


    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                // If activating handymen, check plan limits
                if ($request->status == 1 && default_earning_type() === 'subscription') {
                    $provider_id = auth()->user()->id;
                    
                    // If admin is managing a specific provider's handymen, get that provider_id
                    if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
                        // Get provider_id from the first handyman
                        $first_handyman = User::find($ids[0]);
                        if ($first_handyman) {
                            $provider_id = $first_handyman->provider_id;
                        }
                    }
                    
                    // Get handymen to be activated (currently inactive)
                    $handymen_to_activate = User::whereIn('id', $ids)
                        ->where('status', 0)
                        ->where('provider_id', $provider_id)
                        ->where('user_type', 'handyman')
                        ->get();

                    // Activate sequentially and re-check limits each time.
                    // This prevents exceeding plan limits during bulk activation.
                    $activated_count = 0;
                    $failed_handymen = [];
                    foreach ($handymen_to_activate as $handyman) {
                        if (can_activate_resource($provider_id, 'handyman')) {
                            $handyman->update(['status' => 1]);
                            $activated_count++;
                        } else {
                            $failed_handymen[] = $handyman->id;
                        }
                    }

                    if (count($failed_handymen) > 0) {
                        if ($activated_count > 0) {
                            $message = __('messages.handyman_activated', ['count' => $activated_count]) . ' ' . __('messages.handyman_limit_exceeded');
                            return response()->json(['status' => true, 'message' => $message]);
                        }

                        $limit = get_remaining_limit($provider_id, 'handyman');
                        $message = __('messages.handyman_limit_exceeded', ['limit' => $limit]);
                        return response()->json(['status' => false, 'message' => $message]);
                    }

                    $message = __('messages.handyman_activated', ['count' => $activated_count]);                    
                    
                } elseif ($request->status == 0 && default_earning_type() === 'subscription') {
                    // If deactivating, no need to check limits
                    User::whereIn('id', $ids)->update(['status' => $request->status]);
                    $message = __('messages.handyman_deactivated', ['count' => count($ids)]);
                } else {
                    // For non-subscription, just update all
                    User::whereIn('id', $ids)->update(['status' => $request->status]);
                }
                
                break;

            case 'delete':
                if (!auth()->user()->can('handyman delete') && !auth()->user()->hasRole('demo_admin')) {
                    return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
                }
                User::whereIn('id', $ids)->delete();
                $message = 'Bulk Handyman Deleted';
                break;

            case 'restore':
                if (!auth()->user()->can('handyman delete') && !auth()->user()->hasRole('demo_admin')) {
                    return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
                }
                User::whereIn('id', $ids)->restore();
                $message = 'Bulk Handyman Restored';
                break;

            case 'permanently-delete':
                if (!auth()->user()->can('handyman delete') && !auth()->user()->hasRole('demo_admin')) {
                    return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
                }
                User::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Handyman Permanently Deleted';
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
        if (!auth()->user()->can('handyman add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $auth_user = authSession();

        // Log session data for debugging
        \Log::info('Handyman create page loaded', [
            'handyman_id' => $id,
            'has_error_message' => session()->has('error_message'),
            'error_message' => session('error_message'),
            'all_session' => session()->all()
        ]);

        // Enforce plan limits on page load
        if (default_earning_type() === 'subscription') {
            // For admins editing provider handymen, enforce limits for the handyman's provider
            if ($id && auth()->user()->hasRole(['admin', 'demo_admin'])) {
                $handyman = User::find($id);
                if ($handyman && $handyman->provider_id) {
                    $this->enforcePlanLimits($handyman->provider_id);
                }
            } else {
                // For providers, enforce their own limits
                $this->enforcePlanLimits($auth_user->id);
            }
        }

        $handymandata = User::find($id);
        $pageTitle = __('messages.update_form_title', ['form' => __('messages.handyman')]);

        if ($handymandata == null) {
            $pageTitle = __('messages.add_button_form', ['form' => __('messages.handyman')]);
            $handymandata = new User;
        }else{
            if ($handymandata->provider_id !== auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
                return redirect(route('handyman.index'))->withErrors(trans('messages.demo_permission_denied'));
            }
        }

        return view('handyman.create', compact('pageTitle', 'handymandata', 'auth_user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        if (demoUserPermission()) {
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();
        if (auth()->user()->hasAnyRole(['provider'])) {
            $auth_user = authSession();
            $user_id = $auth_user->id;
            $data['provider_id'] = $user_id;
        }
        
        // VALIDATION: Check plan limits for NEW handyman creation
        if ($request->id == null && default_earning_type() === 'subscription') {
            $validation = validatePlanLimit($data['provider_id'], 'handyman');
            
            if (!$validation['can_create']) {
                $message = $validation['message'];
                
                \Log::info('Handyman creation blocked - plan limit validation failed', [
                    'provider_id' => $data['provider_id'],
                    'validation' => $validation,
                    'message' => $message
                ]);
                
                if ($request->is('api/*')) {
                    return comman_message_response($message);
                } else {
                    return redirect()->back()->withErrors(['handyman' => $message])->withInput();
                }
            }
        }

        // VALIDATION: Check plan limits when editing handyman and activating it
        if ($request->id && $request->id != null && default_earning_type() === 'subscription') {
            $existing_handyman = User::find($request->id);
            
            if ($existing_handyman) {
                // Check if provider is being changed by admin
                $old_provider_id = $existing_handyman->provider_id;
                $new_provider_id = !empty($data['provider_id']) ? $data['provider_id'] : $old_provider_id;
                
                // VALIDATION: Check if admin is changing provider and new provider has reached limit
                if ($old_provider_id != $new_provider_id) {
                    // Check if new provider has active subscription
                    $new_provider_has_subscription = is_any_plan_active($new_provider_id);
                    
                    if ($new_provider_has_subscription) {
                        // Check if new provider can accept this handyman
                        $validation = validatePlanLimit($new_provider_id, 'handyman');
                        
                        if (!$validation['can_create']) {
                            $message = __('messages.handyman_limit_reached_for_plan');
                            
                            \Log::info('Handyman provider change blocked - new provider limit reached', [
                                'handyman_id' => $request->id,
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
                    }
                }
                
                // Cast status to integer for proper comparison
                $new_status = isset($data['status']) ? (int)$data['status'] : $existing_handyman->status;
                $existing_status = (int)$existing_handyman->status;
                
                // If handyman is being activated (status changed from 0 to 1)
                if ($existing_status == 0 && $new_status == 1) {
                    $provider_id = $data['provider_id'];
                    
                    // Use checkActiveOnly = true for activation (count only active handymen)
                    $validation = validatePlanLimit($provider_id, 'handyman', $request->id, true);
                    
                    if (!$validation['can_create']) {
                        $message = $validation['message'];
                        
                        if ($request->is('api/*')) {
                            return comman_message_response($message);
                        } else {
                            return redirect()->route('handyman.create', ['id' => $request->id])
                                ->withInput()
                                ->with('error', $message);
                        }
                    }
                }
            }
        }

        $id = $data['id'];

        $data['user_type'] = $data['user_type'] ?? 'handyman';
        $data['is_featured'] = 0;

        if ($request->has('is_featured')) {
            $data['is_featured'] = 1;
        }

        $data['display_name'] = $data['first_name'] . " " . $data['last_name'];
        // Save User data...
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
            // User data...
            // $user->removeRole($user->user_type);
            $user->fill($data)->update();
        }
        // if ($data['status'] == 1 && auth()->user()->hasAnyRole(['admin'])) {

        
      
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
                Log::error('Handyman verification email failed: ' . $e->getMessage());
                $message = __('messages.email_send_failed_retry_later');
            }
        } 
        // if ($data['status'] == 1 && auth()->user()->hasAnyRole(['admin'])) {
        //     \Log::info("------------this call admin siodee--------");
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
        //         Log::error('Handyman verification email failed: ' . $e->getMessage());
        //     }
        // }
        $user->assignRole($data['user_type']);
        storeMediaFile($user, $request->profile_image, 'profile_image');
        $message = __('messages.update_form', ['form' => __('messages.handyman')]);
        if ($user->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.handyman')]);
        }

        if ($request->is('api/*')) {
            return comman_message_response($message);
        }

        return redirect(route('handyman.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth_user = authSession();
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }
        $providerdata = User::with('providerHandyman')->where('user_type', 'provider')->where('id', $id)->first();
        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return view('handyman.view', compact('pageTitle', 'providerdata', 'auth_user'));
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
        $handyman = User::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.handyman')]);

        if ($handyman != '') {
            $handyman->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.handyman')]);
        }
        if (request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
    public function action(Request $request)
    {
        $id = $request->id;

        $user  = User::withTrashed()->where('id', $id)->first();
        $msg = __('messages.not_found_entry', ['name' => __('messages.handyman')]);
        if ($request->type == 'restore') {
            // Check plan limit before restoring
            $providerId = $user->provider_id;
            
            // Use validatePlanLimit with checkActiveOnly = true to count only active handymen
            // and exclude the handyman being restored
            $validation = validatePlanLimit($providerId, 'handyman', $id, true);
            
            if (!$validation['can_create']) {
                $msg = __('messages.handyman_restore_limit_exceeded');
                if (request()->is('api/*')) {
                    return comman_message_response($msg, 400);
                }
                return comman_custom_response(['message' => $msg, 'status' => false]);
            }

            $user->restore();
            $msg = __('messages.msg_restored', ['name' => __('messages.handyman')]);
        }
        if ($request->type === 'forcedelete') {
            $user->forceDelete();
            $msg = __('messages.msg_forcedelete', ['name' => __('messages.handyman')]);
        }
        if (request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    public function approve($id)
    {
        $handyman = User::find($id);
        
        // Check plan limits before activating handyman
        if (default_earning_type() === 'subscription' && $handyman->provider_id) {
            // Do NOT pass $id as excluding_id - count ALL active handymen
            if (!can_activate_resource($handyman->provider_id, 'handyman')) {
                $limit = get_remaining_limit($handyman->provider_id, 'handyman');
                $message = __('messages.handyman_limit_exceeded', ['limit' => $limit]);
                \Log::info('Handyman approval blocked - limit exceeded', [
                    'handyman_id' => $id,
                    'provider_id' => $handyman->provider_id,
                    'message' => $message
                ]);
                return redirect()->back()->withErrors($message);
            }
        }
        
        $handyman->status = 1;
        $handyman->save();
        $msg = __('messages.approve_successfully');
        \Log::info('Handyman approved successfully', [
            'handyman_id' => $id,
            'provider_id' => $handyman->provider_id
        ]);
        return redirect()->back()->withSuccess($msg);
    }

    public function updateProvider(Request $request)
    {
        $id = $request->id;
        $handyman = User::with('handyman')->findOrFail($id);
        $provider_id = $request->provider_id;

        $handyman->update(['provider_id' => $provider_id]);

        return response()->json(['message' => 'Provider Assign Successfully', 'status' => true]);
    }



    public function getChangePassword(Request $request)
    {
        $id = $request->id;
        $auth_user = authSession();

        $handymandata = User::find($id);
        $pageTitle = __('messages.change_password', ['form' => __('messages.change_password')]);
        if ($handymandata == null) {
            $pageTitle = __('messages.add_button_form', ['form' => __('messages.handyman')]);
            $handymandata = new User;
        }
        return view('handyman.changepassword', compact('pageTitle', 'handymandata', 'auth_user'));
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
                return redirect()->route('handyman.changepassword', ['id' => $user->id])->with('error', $message);
            }
            return redirect()->route('handyman.changepassword', ['id' => $user->id])->with('errors', $validator->errors());
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old, $hashedPassword);

        $same_exits = Hash::check($request->password, $hashedPassword);
        if ($match) {
            if ($same_exits) {
                $message = __('messages.old_new_pass_same');
                return redirect()->route('handyman.changepassword', ['id' => $user->id])->with('error', $message);
            }

            $user->fill([
                'password' => Hash::make($request->password)
            ])->save();
            $message = __('messages.password_change');
            return redirect()->route('handyman.index')->withSuccess($message);
        } else {
            $message = __('messages.valid_password');
            return redirect()->route('handyman.changepassword', ['id' => $user->id])->with('error', $message);
        }
    }
    public function handyman_detail($id)
    {
        $auth_user = authSession();
        $handymandata = User::with(['providerHandyman', 'commission_earning', 'wallet'])
                        ->where('user_type', 'handyman')
                        ->find($id);
        // Fetch handyman data along with related data in a single query


        if (is_null($handymandata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.handyman')]);
            return redirect(route('handyman.index'))->withError($msg);
        }

        if (
            ((auth()->user()->hasRole('handyman') && $handymandata->id !== auth()->user()->id) ||
                (auth()->user()->hasRole('provider')  && $handymandata->provider_id !== auth()->user()->id)) &&
            (!auth()->user()->hasRole(['admin', 'demo_admin']))
        ) {
            return redirect(route('handyman.index'))->withErrors(trans('messages.demo_permission_denied'));
        }
        // Count handyman's booking statuses in a single query
        $data = Booking::whereHas('handymanAdded', function ($query) use ($id) {
            $query->where('handyman_id', $id);
        })->selectRaw(
            'COUNT(CASE WHEN status = "pending" THEN "pending" END) AS PendingStatusCount,
             COUNT(CASE WHEN status = "in_progress"  THEN "InProgress" END) AS InProgressstatuscount,
             COUNT(CASE WHEN status = "completed"  THEN "Completed" END) AS Completedstatuscount,
             COUNT(CASE WHEN status = "accept"  THEN "Accepted" END) AS Acceptedstatuscount,
             COUNT(CASE WHEN status = "on_going"  THEN "Ongoing" END) AS Ongoingstatuscount'
        )->first()->toArray();

        $totalbooking = Booking::whereHas('handymanAdded', function ($query) use ($id) {
            $query->where('handyman_id', $id);
        })->count();
        // Get total handyman payout and unpaid commission earnings
        $totalWithdrawn = HandymanPayout::where('handyman_id', $id)->sum('amount') ?? 0;
        $pendingCommission = $handymandata->commission_earning()
            ->whereHas('getbooking', function ($query) {
                $query->where('status', 'completed');
            })
            ->where('commission_status', 'unpaid')
            ->sum('commission_amount');
        $earning =    $pendingCommission ? $pendingCommission : 0;
        // Calculate total earnings
        $walletAmount = optional($handymandata->wallet)->amount ?? 0;
        $totalEarnings = $totalWithdrawn + $earning;

        $handymanData = [
            'wallet' => $walletAmount,
            'handymanAlreadyWithdrawAmt' => $totalWithdrawn,
            'pendWithdrwan' => $earning,
            'totalEarning' => $totalEarnings,
            'totalbooking' => $totalbooking,
        ];


        $pageTitle = __('messages.view_form_title', ['form' => __('messages.provider')]);
        return view('handyman.detail', compact('pageTitle', 'handymandata', 'handymanData', 'auth_user', 'data'));
    }

    public function review(Request $request, $id)
    {
        $auth_user = authSession();
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }

        $handymandata = User::with('handymanRating')->findOrFail($id);

        if ($request->ajax()) {
            $query = $handymandata->handymanRating()->with('customer');

            if (!empty($request->filter['column_status'])) {
                $query->where('status', $request->filter['column_status']);
            }

            if (!empty($request->search['value'])) {
                $search = $request->search['value'];

                $query->where(function ($q) use ($search) {
                    $q->where('review', 'like', "%{$search}%")
                        ->orWhere('rating', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('display_name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            }

            return DataTables::of($query)
                ->addColumn('user_name', function ($row) {
                    return $row->customer->display_name ?? '-';
                })
                ->addColumn('date', function ($row) {
                    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                    $date = $sitesetup ? json_decode($sitesetup->value) : null;
                    return $date ? date("{$date->date_format} {$date->time_format}", strtotime($row->created_at)) : '-';
                })
                ->editColumn('rating', function ($row) {
                    return number_format($row->rating, 1);
                })
                ->rawColumns(['user_name', 'rating', 'review', 'date'])
                ->make(true);
        }

        return view('handyman.review', compact('handymandata'));
    }

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

            // Check handyman limit
            if (isset($plan_limitation['handyman']) && $plan_limitation['handyman']['is_checked'] === 'on') {
                $handyman_limit = (int)$plan_limitation['handyman']['limit'];
                
                // Get active handymen count - IMPORTANT: Filter by user_type = 'handyman'
                $active_handymen = User::where('provider_id', $provider_id)
                    ->where('user_type', 'handyman')
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

                    \Log::info('Handyman limits enforced on page load', [
                        'provider_id' => $provider_id,
                        'handyman_limit' => $handyman_limit,
                        'active_handymen_before' => $active_handymen->count(),
                        'handymen_deactivated' => $handymen_to_deactivate->count()
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error enforcing plan limits: ' . $e->getMessage(), [
                'provider_id' => $provider_id
            ]);
        }
    }
}
