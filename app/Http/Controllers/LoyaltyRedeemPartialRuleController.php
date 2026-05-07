<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoyaltyRedeemPartialRule;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyRedeemPartialRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->loyalty_redeem_rule_id) {
            $id = $request->loyalty_redeem_rule_id;
            $type = "loyalty_redeem_rule";
        } else {
            $id = null;
            $type = null;
        }

        return view('loyalty-points.redeem_partial_rule_index', compact('id', 'type'));
    }

    public function index_data(Request $request)
    {
        $redeemPartialRules = LoyaltyRedeemPartialRule::withTrashed();

        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $redeemPartialRules = $redeemPartialRules->where('status', $filter['column_status']);
        }

        if ($request->type === 'loyalty_redeem_rule' && $request->id) {
           $redeemPartialRules = $redeemPartialRules->where('redeem_rule_id', $request->id);
        }

        return DataTables::of($redeemPartialRules)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="loyaltyredeempartialrule" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->addColumn('redeem_name', function ($row) {
                return $row->rule_name;
            })
            ->addColumn('point_from', function ($row) {
                return  $row->point_from ;
            })
            ->addColumn('point_to', function ($row) {
                return  $row->point_to ;
            })
            ->addColumn('discount_amount', function ($row) {
                return  $row->amount ;
            })
            ->addColumn('status', function ($row) {
                $disabled = $row->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="redeem_partial_rule_status" '.($row->status ? "checked" : "").' '.$disabled.' value="'.$row->id.'" id="'.$row->id.'" data-id="'.$row->id.'">
                        <label class="custom-control-label" for="'.$row->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($row) {
                return view('loyalty-points.redeem_partial_rule_action', compact('row'))->render();
            })
            ->rawColumns(['check', 'status', 'action'])
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
    public function store(Request $request)
    {
        $request->validate([
            'partial_rule_name' => 'required|string|max:255',
            'point_from' => 'required|integer|min:0',
            'point_to' => 'required|integer|min:0',
            'partial_amount' => 'required|numeric|min:0',
            'partial_status' => 'required|boolean',
            'redeem_rule_id' => 'required|exists:loyalty_redeem_rules,id',
        ]);

        try {
            LoyaltyRedeemPartialRule::create([
                'rule_name' => $request->partial_rule_name,
                'point_from' => $request->point_from,
                'point_to' => $request->point_to,
                'amount' => $request->partial_amount,
                'status' => $request->partial_status,
                'redeem_rule_id' => $request->redeem_rule_id,
            ]);

            return redirect()->back()->with('success', 'Partial rule created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create partial rule: ' . $e->getMessage());
        }
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
    public function edit(string $id)
    {
        $rule = LoyaltyRedeemPartialRule::findOrFail($id);
        return response()->json($rule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'partial_rule_name' => 'required|string|max:255',
            'point_from' => 'required|integer|min:0',
            'point_to' => 'required|integer|min:0',
            'partial_amount' => 'required|numeric|min:0',
            'partial_status' => 'required|boolean',
        ]);

        $rule = LoyaltyRedeemPartialRule::findOrFail($id);

        $rule->update([
            'rule_name' => $request->partial_rule_name,
            'point_from' => $request->point_from,
            'point_to' => $request->point_to,
            'amount' => $request->partial_amount,
            'status' => $request->partial_status,
        ]);

        return response()->json([
                'success' => true,
                'message' => 'Partial rule updated successfully!',
        ]);
    }


     /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        $rule = LoyaltyRedeemPartialRule::findOrFail($id);
        $rule->delete();
        $msg = 'Redeem Rule deleted successfully.';
        return response()->json(['message' => $msg, 'status' => true]);
    }

     /**
     * Restore the specified resource in storage.
     */
    public function restore($id)
    {
        $rule = LoyaltyRedeemPartialRule::withTrashed()->findOrFail($id);
        $rule->restore();
        return response()->json(['message' => 'Redeem Rule restored successfully.', 'status' => true]);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $rule = LoyaltyRedeemPartialRule::withTrashed()->findOrFail($id);
        $rule->forceDelete();
        return response()->json(['message' => 'Redeem Rule permanently deleted successfully.', 'status' => true]);
    }

    /**
     * Bulk action for redeem rules.
     */
     public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $message = 'Redeem Rule Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                LoyaltyRedeemPartialRule::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Redeem Rule Bulk Status Updated';
                break;

            case 'delete':
                LoyaltyRedeemPartialRule::whereIn('id', $ids)->delete();
                $message = 'Redeem Rule Bulk Deleted';
                break;

            case 'restore':
                LoyaltyRedeemPartialRule::whereIn('id', $ids)->restore();
                $message = 'Redeem Rule Bulk Restored';
                break;

            case 'permanently-delete':
                LoyaltyRedeemPartialRule::whereIn('id', $ids)->forceDelete();
                $message = 'Redeem Rule Bulk Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }
}
