<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plans;
use App\Models\PlanLimit;
use App\Models\ProviderSubscription;
use App\Models\StaticData;
use App\Http\Requests\PlanRequest;
use App\Models\Setting;
use Yajra\DataTables\DataTables;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = trans('messages.list_form_title', ['form' => trans('messages.plan')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('plan.index', compact('pageTitle', 'auth_user', 'assets', 'filter'));
    }



    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Plans::query()->list();
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->newQuery();
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })

            ->editColumn('title', function ($query) {
                if (auth()->user()->can('plan edit')) {
                    $link = '<a class="btn-link btn-link-hover" href=' . route('plans.create', ['id' => $query->id]) . '>' . $query->title . '</a>';
                } else {
                    $link = $query->title;
                }
                return $link;
            })

            ->editColumn('type', function ($query) {
                if (!empty($query->type)) {
                    return ucfirst($query->type);
                }
                return '-';
            })
            ->addColumn('level', function ($query) {
                // If identifier is 'free', don't assign any level
                if ($query->identifier === 'free') {
                    return '-';
                }
                // For other plans, level = id - 1
                return 'Level '.$query->id - 1;
            })
            ->editColumn('status', function ($query) {
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="plan_status" ' . ($query->status ? "checked" : "") . '   value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->editColumn('amount', function ($query) {
                $price = !empty($query->amount) ? getPriceFormat($query->amount) : '-';
                return $price;
            })
            ->addColumn('action', function ($plan) {
                return view('plan.action', compact('plan'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['title', 'action', 'status', 'check'])
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
                $branches = Plans::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Plans Status Updated';
                break;

            case 'delete':
                // Keep subscription history rows, just detach deleted plans.
                ProviderSubscription::whereIn('plan_id', $ids)->update(['plan_id' => null]);
                Plans::whereIn('id', $ids)->delete();
                $message = 'Bulk Plans Deleted';
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
        if (!auth()->user()->can('plan add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $auth_user = authSession();

        $plan = Plans::with('planlimit')->find($id);
        $plan_type = StaticData::where('type', 'plan_type')->get();
        $plan_limit = StaticData::where('type', 'plan_limit_type')->get();
        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.plan')]);

        if ($plan == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.plan')]);
            $plan = new Plans;
        }
        $is_in_app_purchase_enable = optional(json_decode(Setting::where('type', 'OTHER_SETTING')->where('key', 'OTHER_SETTING')->value('value'), true))['is_in_app_purchase_enable'] ?? 0;
        $is_free_plan = Plans::where('identifier','free')->first() ? 1 : 0;
        return view('plan.create', compact('pageTitle', 'plan', 'auth_user', 'plan_type', 'plan_limit', 'is_in_app_purchase_enable', 'is_free_plan'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PlanRequest $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $requestData = $request->all();

        // Ensure duration always has a value
        if (empty($requestData['duration'])) {
            $requestData['duration'] = 1;
        }

        // Check for duplicate title if creating new
        $plans = Plans::where('title', '=', $requestData['title'])->first();
        if ($plans !== null && empty($request->id)) {
            return redirect()->back()->withErrors(__('validation.unique', ['attribute' => __('messages.plan')]));
        }

        // Check for duplicate amount (excluding current plan if updating)
        $amountQuery = Plans::where('amount', '=', $requestData['amount']);
        if (!empty($request->id)) {
            $amountQuery = $amountQuery->where('id', '!=', $request->id);
        }
        $duplicateAmount = $amountQuery->first();
        if ($duplicateAmount !== null) {
            return redirect()->back()
                ->withInput()
                ->withErrors(__('validation.unique', ['attribute' => __('messages.amount')]));
        }

        // Validate plan limitations
        $planLimitation = $requestData['plan_limitation'] ?? [];
        $isFreeTrial = isset($requestData['free_trial']) && in_array($requestData['free_trial'], [1, '1', 'on', true], true);
        
        if ($requestData['plan_type'] =='limited' && is_array($planLimitation)) {
            $serviceLimit = null;
            $featuredServiceLimit = null;

            // Get service limit
            if (isset($planLimitation['service']) && isset($planLimitation['service']['is_checked']) && $planLimitation['service']['is_checked'] === 'on') {
                $serviceLimit = $planLimitation['service']['limit'] ?? null;
            }

            // Get featured service limit
            if (isset($planLimitation['featured_service']) && isset($planLimitation['featured_service']['is_checked']) && $planLimitation['featured_service']['is_checked'] === 'on') {
                $featuredServiceLimit = $planLimitation['featured_service']['limit'] ?? null;
            }

            // Validate: featured_service limit cannot exceed service limit
            if ($serviceLimit !== null && $featuredServiceLimit !== null) {
                $serviceLimitValue = (int)$serviceLimit;
                $featuredServiceLimitValue = (int)$featuredServiceLimit;

                if ($featuredServiceLimitValue > $serviceLimitValue) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(__('messages.featured_service_limit_exceeds_service_limit', [
                            'service_limit' => $serviceLimitValue,
                            'featured_service_limit' => $featuredServiceLimitValue
                        ]));
                }
            }

            // Skip price-limit consistency validation for free trial plans
            if (!$isFreeTrial) {
                // Validate plan limits based on amount compared to existing plans
                $planValidator = new \App\Services\PlanValidatorService();
                $planAmount = (float)$requestData['amount'];
                $excludePlanId = $requestData['id'] ?? null;
                
                $validationResult = $planValidator->validatePlanLimits($planAmount, $planLimitation, $excludePlanId);
                
                if (!$validationResult['valid']) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors($validationResult['errors']);
                }
            }
        }

        // Safely get optional fields
        $planData = [
            'title' => $requestData['title'],
            'amount' => $requestData['amount'],
            'status' => $requestData['status'],
            'duration' => $requestData['duration'],
            'description' => $requestData['description'],
            'plan_type' => $requestData['plan_type'],
            'type' => $requestData['type'],
            'playstore_identifier' => $requestData['playstore_identifier'] ?? null,
            'appstore_identifier' => $requestData['appstore_identifier'] ?? null,
        ];

        // implementation free plan functionality so Old Flow changed 
        // if (empty($request->id)) {
        //     $planData['identifier'] = strtolower($requestData['title']);
        // }
        
        // Handle free trial checkbox - when unchecked, it's null, not 0
        $isFreeTrial = isset($requestData['free_trial']) && in_array($requestData['free_trial'], [1, '1', 'on', true], true);
        
        if(isset($requestData['plan_id'])){
            $currentPlan = Plans::where('id', $requestData['plan_id'])->first();
        }
        if(isset($currentPlan) && $currentPlan['identifier'] == 'free'){
            $planData['identifier'] = $isFreeTrial ? 'free' : strtolower($requestData['title']). " plan";
        }else{
            $planData['identifier'] = $isFreeTrial ? 'free' : strtolower($requestData['title']);
        }
        
        // Set amount to 0 if free trial is enabled, otherwise use the provided amount
        $planData['amount'] = $isFreeTrial ? 0 : $requestData['amount'];
        
        // Set trial_period: 1 if free trial is enabled, 0 if disabled
        $planData['trial_period'] = $isFreeTrial ? 1 : 0;

        
        

        $result = Plans::updateOrCreate(['id' => $requestData['id']], $planData);

        if ($result) {
            // Clear existing plan limits
            if ($result->planlimit()->count() > 0) {
                $result->planlimit()->delete();
            }

            // Only save plan limitations when plan type is "limited".
            $planType = strtolower(trim((string)($requestData['plan_type'] ?? '')));
            $planLimitation = $requestData['plan_limitation'] ?? [];
            if (!is_array($planLimitation)) {
                $planLimitation = [];
            }
            
            // Initialize with unchecked state for all limitation types
            $toSave = [
                'service' => ['is_checked' => 'off', 'limit' => null],
                'handyman' => ['is_checked' => 'off', 'limit' => null],
                'featured_service' => ['is_checked' => 'off', 'limit' => null]
            ];
            
            // Update with actual values from request
            foreach (array_keys($toSave) as $key) {
                $keyData = $planLimitation[$key] ?? [];
                if (!is_array($keyData)) {
                    continue;
                }
                
                $isChecked = isset($keyData['is_checked']) && in_array($keyData['is_checked'], ['on', '1', 1, true], true);
                $limit = $keyData['limit'] ?? null;

                if ($isChecked) {
                    // If checked, save with "on" and the limit value
                    $toSave[$key] = [
                        'is_checked' => 'on',
                        'limit' => ($limit !== null && $limit !== '') ? (string)$limit : null
                    ];
                } else {
                    // If not checked, save with "off" and null limit
                    $toSave[$key] = [
                        'is_checked' => 'off',
                        'limit' => null
                    ];
                }
            }

            if ($planType === 'limited') {
                $limitdata = [
                    'plan_id' => $result->id,
                    'plan_limitation' => $toSave
                ];
                PlanLimit::create($limitdata);
            }
        }

        $message = trans('messages.update_form', ['form' => trans('messages.plan')]);
        if ($result->wasRecentlyCreated) {
            $message = trans('messages.save_form', ['form' => trans('messages.plan')]);
        }

        return redirect(route('plans.index'))->withSuccess($message);
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
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $plan = Plans::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.plan')]);

        if ($plan != '') {
            // Keep subscription history row and prevent FK violation when deleting plan.
            ProviderSubscription::where('plan_id', $plan->id)->update(['plan_id' => null]);
            if ($plan->planlimit()->count() > 0) {
                $plan->planlimit()->delete();
            }
            $plan->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.plan')]);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
}
