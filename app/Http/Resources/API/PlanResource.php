<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Format plan_limitation properly
        $planLimitation = optional($this->planlimit)->plan_limitation;
        $formattedPlanLimitation = null;
        
        if ($planLimitation && is_array($planLimitation)) {
            $formattedPlanLimitation = [];
            
            foreach ($planLimitation as $key => $limitation) {
                if (is_array($limitation)) {
                    // Check if is_checked is "on"
                    if (isset($limitation['is_checked']) && $limitation['is_checked'] === 'on') {
                        // Include both is_checked and limit
                        $formattedPlanLimitation[$key] = [
                            'is_checked' => 'on',
                            'limit' => $limitation['limit'] ?? null
                        ];
                    } else {
                        // Only include limit as null
                        $formattedPlanLimitation[$key] = [
                            'is_checked' => 'off',
                            'limit' => null
                        ];
                    }
                } else {
                    // If not an array, just set limit to null
                    $formattedPlanLimitation[$key] = [
                        'is_checked' => 'off',
                        'limit' => null
                    ];
                }
            }
        }
        
        $baseData = [
            'id'                => $this->id,
            'title'             => $this->title,
            'identifier'        => $this->identifier,
            'amount'            => ceil($this->amount),
            'original_amount'   => $this->original_amount ?? null,
            'duration'          => $this->duration,
            'description'       => $this->description,
            'plan_type'         => $this->plan_type,
            'type'              => $this->type,
            'trial_period'      => $this->trial_period,
            'playstore_identifier'      => $this->playstore_identifier,
            'appstore_identifier'      => $this->appstore_identifier,
            'plan_limitation'   => $formattedPlanLimitation
        ];

        // Add proration details if available (set by controller)
        if (isset($this->proration_data)) {
            $baseData['original_price'] = $this->amount;
            $baseData['price'] = $this->proration_data['price'];
            
            if ($this->proration_data['has_proration']) {
                $baseData['remaining_days'] = $this->proration_data['remaining_days'];
                $baseData['remaining_credit'] = $this->proration_data['remaining_credit'];
                $baseData['upgrade_from_plan'] = $this->proration_data['upgrade_from_plan'];
                $baseData['discount_reason'] = $this->proration_data['discount_reason'];
            }
        }
        
        return $baseData;
    }
}