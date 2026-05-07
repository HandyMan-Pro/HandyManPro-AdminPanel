<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\LoyaltyReferralRule;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\LoyaltyReferralRuleRequest;


class LoyaltyReferralRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function index_data(Request $request)
    {
        $referralRules = LoyaltyReferralRule::withTrashed();

        $filter = $request->filter;

        if (isset($filter['column_status']) && $filter['column_status'] !== '') {
            $referralRules->where('status', $filter['column_status']);
        }

        // Get date format from settings
        $date_format = optional(json_decode(
            Setting::where('type', 'site-setup')->where('key', 'site-setup')->value('value') ?? '{}'
        ))->date_format ?? "F j, Y";

        return DataTables::of($referralRules)
            ->addColumn('check', function($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="loyaltyreferrerule" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->addColumn('referrer_points', function($row) {
                return  $row->referrer_points;
            })
            ->addColumn('referred_user_points', function($row) {
                return  $row->referred_user_points;
            })
            ->addColumn('max_referrals_per_user', function($row) {
                return  $row->max_referrals_per_user;
            })
            ->addColumn('expiry_days', function($row) use ($date_format) {
                return date($date_format, strtotime($row->start_date)) . ' - ' . date($date_format, strtotime($row->end_date));
            })
            ->addColumn('status', function ($row) {
                $disabled = $row->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input change_status" data-type="referral_rule_status" '.($row->status ? "checked" : "").' '.$disabled.' value="'.$row->id.'" id="referral_rule_'.$row->id.'" data-id="'.$row->id.'">
                        <label class="custom-control-label" for="referral_rule_'.$row->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function ($row) {
                return view('loyalty-points.referral_rule_action', compact('row'))->render();
            })
            ->rawColumns(['check','expiry_days', 'status', 'action'])
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

    public function store(LoyaltyReferralRuleRequest $request)
    {
        // Check if a referral rule already exists
        $existingRuleCount = LoyaltyReferralRule::withTrashed()->count();

        if ($existingRuleCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Only one referral rule can be created. Please edit or delete the existing rule.',
            ], 422);
        }

        // Split expiry date range
        [$start_date, $end_date] = $this->parseDateRange($request->expiry_days);

        $data = $request->validated();
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        unset($data['expiry_days']); // Remove temporary input

        LoyaltyReferralRule::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Loyalty Referral Rule created successfully!',
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
        $rule = LoyaltyReferralRule::find($id);

        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Referral Rule not found']);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $rule->id,
                'loyalty_type' => $rule->loyalty_type,
                'referrer_points' => $rule->referrer_points,
                'referred_user_points' => $rule->referred_user_points,
                'max_referrals_per_user' => $rule->max_referrals_per_user,
                'expiry_days' => $rule->start_date . ' to ' . $rule->end_date,
                'status' => $rule->status,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rule = LoyaltyReferralRule::find($id);

        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Referral Rule not found']);
        }

        [$start_date, $end_date] = $this->parseDateRange($request->expiry_days);

        $data = $request->all();
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        unset($data['expiry_days']);

        $rule->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Loyalty Referral Rule updated successfully!',
        ]);
    }

     /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request, $id)
    {
        $rule = LoyaltyReferralRule::find($id);
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Referral Rule not found']);
        }
        $rule->delete();
        $msg = 'Loyalty Referral Rule deleted successfully.';
        return response()->json(['message' => $msg, 'status' => true]);
    }

    /**
     * Restore the specified resource in storage.
     */
    public function restore(Request $request, $id)
    {
        $rule = LoyaltyReferralRule::withTrashed()->find($id);
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Referral Rule not found']);
        }
        $rule->restore();
        return response()->json(['message' => 'Loyalty Referral Rule restored successfully.', 'status' => true]);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete(Request $request, $id)
    {
        $rule = LoyaltyReferralRule::withTrashed()->find($id);
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Loyalty Referral Rule not found']);
        }
        $rule->forceDelete();
        return response()->json(['message' => 'Loyalty Referral Rule permanently deleted successfully.', 'status' => true]);
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
                LoyaltyReferralRule::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Loyalty Referral Status Updated';
                break;

            case 'delete':
                LoyaltyReferralRule::whereIn('id', $ids)->delete();
                $message = 'Bulk Loyalty Referral Rule Deleted';
                break;

            case 'restore':
                LoyaltyReferralRule::whereIn('id', $ids)->restore();
                $message = 'Bulk Loyalty Referral Rule Restored';
                break;

            case 'permanently-delete':
                LoyaltyReferralRule::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Loyalty Referral Rule Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Helper method to split "Y-m-d to Y-m-d" into start and end dates
     */
    private function parseDateRange(?string $range): array
    {
        if (!$range) {
            return [null, null];
        }

        $dates = explode(' to ', $range);
        $start_date = $dates[0] ?? null;
        $end_date = $dates[1] ?? null;

        return [$start_date, $end_date];
    }
}
