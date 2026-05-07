<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyReferralRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'referrer_points'         => 'required|integer|min:1',
            'referred_user_points'    => 'required|integer|min:1',
            'max_referrals_per_user'  => 'required|integer|min:1',
            'expiry_days'             => 'required|string',
            'status'                  => 'required|in:1,0',
        ];
    }

    /**
     * Custom error messages (optional).
     */
    public function messages(): array
    {
        return [
            'referrer_points.required'        => 'Please enter referrer points.',
            'referrer_points.min'             => 'Referrer points must be greater than 0.',
            'referred_user_points.required'   => 'Please enter referred user points.',
            'referred_user_points.min'        => 'Referred user points must be greater than 0.',
            'max_referrals_per_user.required' => 'Please enter max referrals per user.',
            'max_referrals_per_user.min'      => 'Max referrals per user must be greater than 0.',
            'expiry_days.required'            => 'Please select an expiry date range.',
            'status.required'                 => 'Please select a status.',
        ];
    }
}
