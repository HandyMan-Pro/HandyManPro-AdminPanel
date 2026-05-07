<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoyaltyRedeemRule;
use App\Models\LoyaltyRedeemServiceMapping;
use App\Models\LoyaltyRedeemPartialRule;
use App\Http\Requests\LoyaltyRedeemRuleRequest;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyRedeemRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index_data(Request $request)
    {
        $redeemRules = LoyaltyRedeemRule::withTrashed()->with('serviceMappings', 'partialRules')->orderByDesc('created_at');;

        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $redeemRules->where('status', $filter['column_status']);
        }

        return DataTables::of($redeemRules)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('loyalty_type', 'like', "%{$search}%")
                          ->orWhere('redeem_type', 'like', "%{$search}%")
                          ->orWhere('threshold_points', 'like', "%{$search}%")
                          ->orWhere('max_discount', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="loyaltyredeemrule" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('loyalty_type', function ($row) {
                if ($row->loyalty_type === 'service') {
                    return 'Service';
                } elseif ($row->loyalty_type === 'package_service') {
                    return 'Package';
                } elseif ($row->loyalty_type === 'category_category') {
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
                                href="' . route('service.index', ['loyality_redeem_rule_id' => $row->id]) . '"
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
                                href="' . route('servicepackage.index', ['loyality_redeem_rule_id' => $row->id]) . '"
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
                                href="' . route('category.index', ['loyality_redeem_rule_id' => $row->id]) . '"
                                data-bs-toggle="tooltip" title="View Category Details">'
                                . $count .
                                '</a>
                            </div>';

                }
            })
            ->addColumn('redeem_type', function ($row) {
                if ($row->redeem_type === 'partial') {
                    return '<div style="text-align:center;">Partial</div>';
                } else {
                    return '<div style="text-align:center;">Full</div>';
                }
            })
            ->addColumn('partial_redeem_rules', function ($row) {
                if ($row->redeem_type === 'partial' && $row->partialRules->count() > 0) {
                    $count = $row->partialRules->count();
                    return '<div style="text-align:center;"><a class="btn-link btn-link-hover" href="' . route('view.partial_rule', ['loyalty_redeem_rule_id' => $row->id]) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="View Partial Redeem Rules">' . $count . '</a></div>';
                } else {
                    return '<div style="text-align:center;">-</div>';
                }
            })
            ->editColumn('threshold_points', function ($row) {
                if ($row->threshold_points === null) {
                    return '<div style="text-align:center;">-</div>';
                } else {
                    return '<div style="text-align:center;">' . $row->threshold_points . '</div>';
                }
            })
            ->editColumn('max_discount', function ($row) {
                if ($row->max_discount === null) {
                    return '<div style="text-align:center;">-</div>';
                } else {
                    return '<div style="text-align:center;">' . $row->max_discount . '</div>';
                }
            })
            ->addColumn('stackable', function ($row) {
                if ($row->is_stackable) {
                    return '<div style="text-align:center;"><span class="text-success">Yes</span></div>';
                } else {
                    return '<div style="text-align:center;"><span class="text-danger">No</span></div>';
                }
            })
            ->addColumn('status', function ($row) {
                $disabled = $row->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="redeem_rule_status" '.($row->status ? "checked" : "").' '.$disabled.' value="'.$row->id.'" id="redeem_rule_'.$row->id.'" data-id="'.$row->id.'">
                        <label class="custom-control-label" for="redeem_rule_'.$row->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($row) {
                return view('loyalty-points.redeem_rule_action', compact('row'))->render();
            })
            ->rawColumns(['check', 'loyalty_type', 'service_count', 'redeem_type', 'partial_redeem_rules', 'threshold_points', 'max_discount', 'stackable', 'status', 'action'])
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
    public function store(LoyaltyRedeemRuleRequest $request)
    {
        $rule = LoyaltyRedeemRule::create([
            'loyalty_type' => $request->redeem_loyalty_type,
            'redeem_type' => $request->redeem_type,
            'threshold_points' => $request->threshold_points,
            'max_discount' => $request->max_discount,
            'status' => $request->status ?? true,
            'is_stackable' => $request->is_stackable ? 1 : 0,
        ]);

        if (!empty($request->redeem_service_id)) {
            foreach ($request->redeem_service_id as $serviceId) {
                LoyaltyRedeemServiceMapping::create([
                    'loyalty_redeem_id' => $rule->id,
                    'service_id' => $serviceId,
                    'loyalty_type' => $request->redeem_loyalty_type,
                ]);
            }
        }

        if ($request->redeem_type === 'partial' && $request->has('partial_rule_name')) {
            foreach ($request->partial_rule_name as $index => $ruleName) {
                LoyaltyRedeemPartialRule::create([
                    'redeem_rule_id' => $rule->id,
                    'rule_name' => $ruleName,
                    'point_from' => $request->point_from[$index] ?? 0,
                    'point_to' => $request->point_to[$index] ?? 0,
                    'amount' => $request->partial_amount[$index] ?? 0,
                    'status' => isset($request->partial_status[$index]) ? (bool)$request->partial_status[$index] : true,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Loyalty Redeem Rule created successfully!',
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
        $rule = LoyaltyRedeemRule::with(['serviceMappings', 'partialRules'])->find($id);

        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Redeem Rule not found']);
        }
        $serviceIds = $rule->serviceMappings->pluck('service_id');

        $data = [
            'id' => $rule->id,
            'loyalty_type' => $rule->loyalty_type,
            'service_id' => $serviceIds,
            'redeem_type' => $rule->redeem_type,
            'threshold_points' => $rule->threshold_points,
            'max_discount' => $rule->max_discount,
            'status' => $rule->status,
            'is_stackable' => $rule->is_stackable,
        ];

        // Add partial rules data if redeem type is partial
        if ($rule->redeem_type === 'partial' && $rule->partialRules->count() > 0) {
            $data['partial_rules'] = $rule->partialRules->map(function ($partialRule) {
                return [
                    'rule_name' => $partialRule->rule_name,
                    'point_from' => $partialRule->point_from,
                    'point_to' => $partialRule->point_to,
                    'partial_amount' => $partialRule->amount,
                    'partial_status' => $partialRule->status,
                ];
            })->toArray();
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LoyaltyRedeemRuleRequest $request, $id)
    {
        $rule = LoyaltyRedeemRule::find($id);

        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Redeem Rule not found']);
        }

        // Update main rule
        $rule->update([
            'loyalty_type' => $request->redeem_loyalty_type,
            'redeem_type' => $request->redeem_type,
            'threshold_points' => $request->threshold_points,
            'max_discount' => $request->max_discount,
            'status' => $request->status,
            'is_stackable' => $request->is_stackable ? 1 : 0,
        ]);

        // Delete old service mappings
        LoyaltyRedeemServiceMapping::where('loyalty_redeem_id', $rule->id)->delete();

        // Create new service mappings
        if (!empty($request->redeem_service_id)) {
            foreach ($request->redeem_service_id as $serviceId) {
                LoyaltyRedeemServiceMapping::create([
                    'loyalty_redeem_id' => $rule->id,
                    'service_id' => $serviceId,
                    'loyalty_type' => $request->redeem_loyalty_type,
                ]);
            }
        }

        // Delete old partial rules
        LoyaltyRedeemPartialRule::where('redeem_rule_id', $rule->id)->delete();

        // Create new partial rules if redeem type is partial
        if ($request->redeem_type === 'partial' && $request->has('partial_rule_name')) {
            foreach ($request->partial_rule_name as $index => $ruleName) {
                LoyaltyRedeemPartialRule::create([
                    'redeem_rule_id' => $rule->id,
                    'rule_name' => $ruleName,
                    'point_from' => $request->point_from[$index] ?? 0,
                    'point_to' => $request->point_to[$index] ?? 0,
                    'amount' => $request->partial_amount[$index] ?? 0,
                    'status' => isset($request->partial_status[$index]) ? (bool)$request->partial_status[$index] : true,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Loyalty Redeem Rule updated successfully!',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        $rule = LoyaltyRedeemRule::find($id);
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Redeem Rule not found']);
        }
        $rule->delete();
        $msg = 'Loyalty Redeem Rule deleted successfully.';
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Restore the specified resource in storage.
     */
    public function restore($id)
    {
        $rule = LoyaltyRedeemRule::withTrashed()->find($id);
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Redeem Rule not found']);
        }
        $rule->restore();
        return response()->json(['message' => 'Loyalty Redeem Rule restored successfully.', 'status' => true]);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $rule = LoyaltyRedeemRule::withTrashed()->find($id);
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Redeem Rule not found']);
        }
        $rule->forceDelete();
        return response()->json(['message' => 'Loyalty Redeem Rule permanently deleted successfully.', 'status' => true]);
    }

    /**
     * Bulk action for redeem rules.
     */
     public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $ids = array_filter($ids); // Remove empty values

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No items selected']);
        }

        $actionType = $request->action_type;
        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                LoyaltyRedeemRule::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Loyalty Redeem Rule Status Updated';
                break;

            case 'delete':
                LoyaltyRedeemRule::whereIn('id', $ids)->delete();
                $message = 'Bulk Loyalty Redeem Rule Deleted';
                break;

            case 'restore':
                LoyaltyRedeemRule::whereIn('id', $ids)->restore();
                $message = 'Bulk Loyalty Redeem Rule Restored';
                break;

            case 'permanently-delete':
                LoyaltyRedeemRule::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Loyalty Redeem Rule Permanently Deleted';
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
                $data = \App\Models\Service::select('id', 'name')->where('status', 1)->get();
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
}
