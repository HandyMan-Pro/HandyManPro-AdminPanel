<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyRedeemRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'redeem_loyalty_type' => 'required|in:service,package_service,category',
            'redeem_type'         => 'required|in:full,partial',
            'status'              => 'nullable|boolean',
            'is_stackable'        => 'nullable|boolean',
        ];

        // FULL RULE
        if ($this->redeem_type === 'full') {
            $rules['threshold_points'] = 'required|integer|min:0';
            $rules['max_discount']     = 'required|numeric|min:0';
        }

        // PARTIAL RULE
        if ($this->redeem_type === 'partial') {
            $rules['partial_rule_name'] = 'required|array|min:1';
            $rules['partial_rule_name.*'] = 'required|string';

            $rules['point_from'] = 'required|array|min:1';
            $rules['point_from.*'] = 'required|integer|min:0';

            $rules['point_to'] = 'required|array|min:1';
            $rules['point_to.*'] = 'required|integer|min:0';

            $rules['partial_amount'] = 'required|array|min:1';
            $rules['partial_amount.*'] = 'required|numeric|min:0';

            $rules['partial_status'] = 'nullable|array';
            $rules['partial_status.*'] = 'nullable|boolean';
        }

        return $rules;
    }
}
