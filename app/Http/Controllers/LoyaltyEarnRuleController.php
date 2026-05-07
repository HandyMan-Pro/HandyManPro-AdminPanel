<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\LoyaltyEarnRule;
use Yajra\DataTables\Facades\DataTables;
use App\Models\LoyaltyEarnServiceMapping;
use App\Http\Requests\LoyaltyEarnRuleRequest;

class LoyaltyEarnRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('loyalty-points.loyalty');
    }

    public function index_data(Request $request)
    {
        // Show latest created rules first (new entries at top)
        $earnRules = LoyaltyEarnRule::withTrashed()
            ->with('serviceMappings')
            ->orderByDesc('created_at');

        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $earnRules->where('status', $filter['column_status']);
        }

        // Get date format from settings
        $date_format = optional(json_decode(
            Setting::where('type', 'site-setup')->where('key', 'site-setup')->value('value') ?? '{}'
        ))->date_format ?? "F j, Y";

        return DataTables::of($earnRules)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('loyalty_type', 'like', "%{$search}%")
                          ->orWhere('minimum_amount', 'like', "%{$search}%")
                          ->orWhere('maximum_amount', 'like', "%{$search}%")
                          ->orWhere('points', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('check', function($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="loyaltyearnrule" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('loyalty_type', function($row) {
                if($row->loyalty_type === 'service'){
                    return 'Service';
                } elseif($row->loyalty_type === 'package_service'){
                    return 'Package';
                } elseif($row->loyalty_type === 'category'){
                    return 'Category';
                } else {
                    return $row->loyalty_type;
                }
            })
           ->addColumn('service_count', function($row) {

                $count = $row->serviceMappings->count();

                if ($row->loyalty_type === 'service') {

                    if ($count === 0) {
                        return '<div style="text-align:center;">All Services</div>';
                    }

                    return '<div style="text-align:center;">
                                <a class="btn-link btn-link-hover"
                                href="' . route('service.index', ['loyality_earn_rule_id' => $row->id]) . '"
                                data-bs-toggle="tooltip" title="View Service Details">'
                                . $count .
                                '</a>
                            </div>';

                } elseif ($row->loyalty_type === 'package_service') {

                    if ($count === 0) {
                        return '<div style="text-align:center;">All Package Services</div>';
                    }

                    return '<div style="text-align:center;">
                                <a class="btn-link btn-link-hover"
                                href="' . route('servicepackage.index', ['loyality_earn_rule_id' => $row->id]) . '"
                                data-bs-toggle="tooltip" title="View Package Service Details">'
                                . $count .
                                '</a>
                            </div>';

                } elseif ($row->loyalty_type === 'category') {

                    if ($count === 0) {
                        return '<div style="text-align:center;">All Categories</div>';
                    }

                    return '<div style="text-align:center;">
                                <a class="btn-link btn-link-hover"
                                href="' . route('category.index', ['loyality_earn_rule_id' => $row->id]) . '"
                                data-bs-toggle="tooltip" title="View Category Details">'
                                . $count .
                                '</a>
                            </div>';

                }
            })
            ->editColumn('minimum_amount', function($row) {
                return $row->minimum_amount;
            })
            ->editColumn('maximum_amount', function($row) {
                return $row->maximum_amount;
            })
            ->editColumn('points', function($row) {
                return $row->points;
            })
            ->addColumn('stackable', function($row) {
                if ($row->is_stackable) {
                    return '<div style="text-align:center;"><span class="text-success">Yes</span></div>';
                } else {
                    return '<div style="text-align:center;"><span class="text-danger">No</span></div>';
                }
            })
            ->addColumn('expiry_days', function($row) use ($date_format) {
                return date($date_format, strtotime($row->start_date)) . ' - ' . date($date_format, strtotime($row->end_date));
            })
            ->addColumn('status', function ($row) {
                $disabled = $row->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="earn_rule_status" '.($row->status ? "checked" : "").' '.$disabled.' value="'.$row->id.'" id="earn_rule_'.$row->id.'" data-id="'.$row->id.'">
                        <label class="custom-control-label" for="earn_rule_'.$row->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($row) {
                return view('loyalty-points.earn_rule_action', compact('row'))->render();
            })
            ->rawColumns(['check','loyalty_type','stackable', 'service_count','minimum_amount', 'maximum_amount', 'points', 'status', 'action'])
            ->toJson();
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoyaltyEarnRuleRequest $request)
    {
        // Split date range into start and end
            $dates = explode(' to ', $request->expiry_days);
            $start_date = $dates[0] ?? null;
            $end_date = $dates[1] ?? null;

        // Create main rule
        $rule = LoyaltyEarnRule::create([
            'loyalty_type' => $request->earn_loyalty_type,
            'minimum_amount' => $request->minimum_amount,
            'maximum_amount' => $request->maximum_amount,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'points' => $request->points,
            'status' => $request->status ?? true,
            'is_stackable' => $request->is_stackable ? 1 : 0,
        ]);

        // Create mappings for each selected service
        if (!empty($request->earn_service_id)) {
            foreach ($request->earn_service_id as $serviceId) {
                LoyaltyEarnServiceMapping::create([
                    'loyalty_earn_id' => $rule->id,
                    'service_id' => $serviceId,
                    'loyalty_type' => $request->earn_loyalty_type,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Loyalty Earn Rule created successfully!',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $rule = LoyaltyEarnRule::with('serviceMappings')->findOrFail($id);
        $serviceIds = $rule->serviceMappings->pluck('service_id');
        return response()->json([
            'status' => true,
            'data' => [
                'id' => $rule->id,
                'loyalty_type' => $rule->loyalty_type,
                'service_id' => $serviceIds,
                'minimum_amount' => $rule->minimum_amount,
                'maximum_amount' => $rule->maximum_amount,
                'points' => $rule->points,
                'expiry_days' => $rule->start_date . ' to ' . $rule->end_date,
                'status' => $rule->status,
                'is_stackable' => $rule->is_stackable,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LoyaltyEarnRuleRequest $request, $id)
    {

        $rule = LoyaltyEarnRule::findOrFail($id);

        // Split date range
        $dates = explode(' to ', $request->expiry_days);
        $start_date = $dates[0] ?? null;
        $end_date = $dates[1] ?? null;

        // Update main rule
        $rule->update([
            'loyalty_type' => $request->earn_loyalty_type,
            'minimum_amount' => $request->minimum_amount,
            'maximum_amount' => $request->maximum_amount,
            'points' => $request->points,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $request->status,
            'is_stackable' => $request->is_stackable ? 1 : 0,
        ]);

        // Delete old mappings
        LoyaltyEarnServiceMapping::where('loyalty_earn_id', $rule->id)->delete();

        // Create new mappings
        if (!empty($request->earn_service_id)) {
            foreach ($request->earn_service_id as $serviceId) {
                LoyaltyEarnServiceMapping::create([
                    'loyalty_earn_id' => $rule->id,
                    'service_id' => $serviceId,
                    'loyalty_type' => $request->earn_loyalty_type,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Loyalty Earn Rule updated successfully!',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        $rule = LoyaltyEarnRule::findOrFail($id);
        $rule->delete();
        $msg = 'Loyalty Earn Rule deleted successfully.';
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Restore the specified resource in storage.
     */
    public function restore($id)
    {
        $rule = LoyaltyEarnRule::withTrashed()->findOrFail($id);
        $rule->restore();
        return response()->json(['message' => 'Loyalty Earn Rule restored successfully.', 'status' => true]);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $rule = LoyaltyEarnRule::withTrashed()->findOrFail($id);
        $rule->forceDelete();
        return response()->json(['message' => 'Loyalty Earn Rule permanently deleted successfully.', 'status' => true]);
    }

    /**
     * Bulk action for earn rules.
     */
    public function bulk_action(Request $request)
    {
        $ids = array_filter(explode(',', $request->rowIds ?? ''));

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No items selected']);
        }

        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                LoyaltyEarnRule::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Loyalty Earn Status Updated';
                break;

            case 'delete':
                LoyaltyEarnRule::whereIn('id', $ids)->delete();
                $message = 'Bulk Loyalty Earn Rule Deleted';
                break;

            case 'restore':
                LoyaltyEarnRule::whereIn('id', $ids)->restore();
                $message = 'Bulk Loyalty Earn Rule Restored';
                break;

            case 'permanently-delete':
                LoyaltyEarnRule::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Loyalty Earn Rule Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Get service type data.
     */
    public function getServiceTypeData(Request $request)
    {
        $type = $request->get('type');
        $data = collect();

        switch ($type) {
            case 'service':
                $data = \App\Models\Service::select('id', 'name')->where('status', 1)->where('service_request_status', 'approve')->get();
                break;

            case 'package_service':
                $data = \App\Models\ServicePackage::select('id', 'name')->where('status', 1)->get();
                break;

            case 'category':
                $data = \App\Models\Category::select('id', 'name')->where('status', 1)->get();
                break;
        }

        return response()->json(['data' => $data]);
    }


    /**
     * Get earn points for API.
     */
    public function userEarnPointsByService(Request $request)
    {
        if($request->type === 'service'){
            $earn_points = LoyaltyEarnRule::userEarnPointsByService($request->service_id, $request->sub_total);
        } elseif($request->type === 'package_service'){
            $earn_points = LoyaltyEarnRule::userEarnPointsByPackage($request->service_id, $request->sub_total);
        } else {
            $earn_points = 0;
        }

        return response()->json(['earn_points' => $earn_points]);
    }
}
