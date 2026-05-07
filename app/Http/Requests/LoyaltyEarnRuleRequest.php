<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyEarnRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all authorized users to submit the form
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'earn_loyalty_type'    => 'required|in:service,package_service,category',
            'minimum_amount'  => 'required|numeric|min:1',
            'maximum_amount'  => 'nullable|numeric|gte:minimum_amount',
            'points'          => 'required|numeric|min:1',
            'is_stackable'    => 'nullable|boolean',
            'status'          => 'nullable|boolean',
            'expiry_days'     => 'required|string',
        ];
    }

    /**
     * Custom validation messages (optional).
     */
    public function messages(): array
    {
        return [
            'earn_loyalty_type.required'   => 'Please select a loyalty type.',
            'minimum_amount.required' => 'Minimum amount is required.',
            'maximum_amount.gte'      => 'Maximum amount must be greater than minimum amount.',
            'points.required'         => 'Points are required.',
            'expiry_days.required'    => 'Please select an expiry date range.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_stackable' => $this->boolean('is_stackable'),
            'status'       => $this->boolean('status'),
        ]);
    }
}
