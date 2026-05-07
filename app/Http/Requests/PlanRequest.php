<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;
        $isFreeTrial = request()->has('free_trial') || request()->free_trial == 1 || request()->free_trial === 'on';
        return [
            'title'     => 'required_if:id,=,null|min:3|max:255|unique:plans,title,' . ($id ?: 'NULL'),
            'plan_type' => 'required|in:limited,unlimited,free',
            'amount' => $isFreeTrial ? 'nullable|integer|min:0' : 'required|integer|min:1',
            // Keep plan description small for consistent display in UI and subscription detail.
            'description' => 'nullable|string|max:250',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $planType = $this->input('plan_type');
            if ($planType !== 'limited' && $planType !== 'Limited') {
                return;
            }

            $planLimitation = $this->input('plan_limitation');
            if (!is_array($planLimitation)) {
                $planLimitation = [];
            }

            $checked = [];
            $missingLimit = [];

            $keys = ['service', 'handyman', 'featured_service'];

            foreach ($keys as $key) {
                $keyData = $planLimitation[$key] ?? [];
                if (!is_array($keyData)) {
                    $keyData = [];
                }
                $isCheckedVal = $keyData['is_checked'] ?? null;
                $isChecked = in_array($isCheckedVal, ['on', '1', 1, true], true);
                $limit = $keyData['limit'] ?? null;
                $limitFilled = $limit !== null && $limit !== '' && (is_numeric($limit) ? (float)$limit >= 0 : true);

                if ($isChecked) {
                    if ($limitFilled) {
                        $checked[] = $key;
                    } else {
                        $missingLimit[] = $key;
                    }
                }
            }

            if (count($checked) === 0 && count($missingLimit) === 0) {
                $validator->errors()->add(
                    'plan_limitation',
                    __('messages.plan_limited_require_one_short')
                );
                return;
            }

            if (count($missingLimit) > 0) {
                foreach ($missingLimit as $key) {
                    $validator->errors()->add(
                        "plan_limitation.{$key}.limit",
                        __('messages.plan_limited_set_limit_required_short')
                    );
                }
            }

            // Skip price-limit consistency validation for free trial plans
            $isFreeTrial = $this->has('free_trial') && in_array($this->input('free_trial'), [1, '1', 'on', true], true);
            if (!$isFreeTrial) {
                // Validate price-limit consistency with existing plans
                $this->validatePriceLimitConsistency($validator, $planLimitation);
            }
        });
    }

    /**
     * Validate that plan limits are consistent with plan price
     * Lower-priced plans must have lower or equal limits
     * Higher-priced plans must have higher or equal limits
     * Also prevents duplicate amounts and duplicate limit combinations
     */
    protected function validatePriceLimitConsistency(Validator $validator, array $planLimitation)
    {
        $currentAmount = (float)$this->input('amount', 0);
        $currentPlanId = $this->input('id');

        // Get all existing plans except the current one being edited
        $existingPlans = \App\Models\Plans::with('planlimit')
            ->where('plan_type', 'limited')
            ->when($currentPlanId, function ($query) use ($currentPlanId) {
                return $query->where('id', '!=', $currentPlanId);
            })
            ->get();

        if ($existingPlans->isEmpty()) {
            return; // No existing plans to compare against
        }

        // Extract current plan limits
        $currentLimits = $this->extractLimits($planLimitation);

        // Check for duplicate amount
        foreach ($existingPlans as $existingPlan) {
            $existingAmount = (float)$existingPlan->amount;
            
            if ($currentAmount == $existingAmount) {
                $validator->errors()->add(
                    'amount',
                    __('messages.plan_amount_already_exists')
                );
                return;
            }
        }

        // Check for duplicate limit combinations and price-limit consistency
        foreach ($existingPlans as $existingPlan) {
            $existingAmount = (float)$existingPlan->amount;
            $existingLimits = $this->extractExistingPlanLimits($existingPlan);

            // Skip if existing plan has no limits
            if (empty($existingLimits)) {
                continue;
            }

            // Check if any individual limit matches (prevent duplicate limits for any resource type)
            foreach ($currentLimits as $key => $currentLimit) {
                $existingLimit = $existingLimits[$key] ?? null;
                
                // Skip if either limit is null (unlimited)
                if ($currentLimit === null || $existingLimit === null) {
                    continue;
                }
                
                // If the limit values match for this resource type, it's a duplicate
                if ($currentLimit === $existingLimit) {
                    $resourceName = ucfirst(str_replace('_', ' ', $key));
                    $validator->errors()->add(
                        "plan_limitation.{$key}.limit",
                        __('messages.plan_limit_already_exists_for_resource', [
                            'resource' => $resourceName,
                            'limit' => $currentLimit
                        ])
                    );
                    return;
                }
            }

            // If current plan is cheaper, all limits must be <= existing plan limits
            if ($currentAmount < $existingAmount) {
                foreach ($currentLimits as $key => $currentLimit) {
                    $existingLimit = $existingLimits[$key] ?? null;
                    
                    // Skip if either limit is unlimited
                    if ($currentLimit === null || $existingLimit === null) {
                        continue;
                    }

                    if ($currentLimit > $existingLimit) {
                        $validator->errors()->add(
                            'plan_limitation',
                            __('messages.plan_limit_higher_than_expensive_plan')
                        );
                        return;
                    }
                }
            }

            // If current plan is more expensive, all limits must be >= existing plan limits
            if ($currentAmount > $existingAmount) {
                foreach ($currentLimits as $key => $currentLimit) {
                    $existingLimit = $existingLimits[$key] ?? null;
                    
                    // Skip if either limit is unlimited
                    if ($currentLimit === null || $existingLimit === null) {
                        continue;
                    }

                    if ($currentLimit < $existingLimit) {
                        $validator->errors()->add(
                            'plan_limitation',
                            __('messages.plan_limit_lower_than_cheaper_plan')
                        );
                        return;
                    }
                }
            }
        }
    }


    /**
     * Extract limits from plan_limitation array
     * Returns array with numeric limits or null for unlimited
     */
    protected function extractLimits(array $planLimitation): array
    {
        $limits = [];
        $keys = ['service', 'handyman', 'featured_service'];

        foreach ($keys as $key) {
            $keyData = $planLimitation[$key] ?? [];
            if (!is_array($keyData)) {
                $keyData = [];
            }

            $isChecked = in_array($keyData['is_checked'] ?? null, ['on', '1', 1, true], true);
            $limit = $keyData['limit'] ?? null;

            if ($isChecked && $limit !== null && $limit !== '') {
                $limits[$key] = (int)$limit;
            } else {
                $limits[$key] = null; // Unlimited
            }
        }

        return $limits;
    }

     /**
     * Extract limits from existing plan model
     * Returns array with numeric limits or null for unlimited
     */
    protected function extractExistingPlanLimits(\App\Models\Plans $plan): array
    {
        $limits = [];
        
        if (!$plan->planlimit || !$plan->planlimit->plan_limitation) {
            return $limits;
        }

        $planLimitation = $plan->planlimit->plan_limitation;
        if (is_string($planLimitation)) {
            $planLimitation = json_decode($planLimitation, true);
        }

        if (!is_array($planLimitation)) {
            return $limits;
        }

        $keys = ['service', 'handyman', 'featured_service'];

        foreach ($keys as $key) {
            $keyData = $planLimitation[$key] ?? [];
            if (!is_array($keyData)) {
                $keyData = [];
            }

            $isChecked = in_array($keyData['is_checked'] ?? null, ['on', '1', 1, true], true);
            $limit = $keyData['limit'] ?? null;

            if ($isChecked && $limit !== null && $limit !== '') {
                $limits[$key] = (int)$limit;
            } else {
                $limits[$key] = null; // Unlimited
            }
        }

        return $limits;
    }

    public function messages()
    {
        return [
            'title.required_if' => __('messages.title_required'),
            'title.min' => __('messages.title_min_3_characters'),
            'title.max' => __('messages.title_max_255_characters'),
            'title.unique' => __('messages.title_already_exists'),
            'amount.required' => __('messages.amount_required'),
            'amount.integer' => __('messages.amount_must_be_integer'),
            'amount.min' => __('messages.amount_min_value'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if (request()->is('api*')) {
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
