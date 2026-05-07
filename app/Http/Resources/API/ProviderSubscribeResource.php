<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderSubscribeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Get original amount from plan relationship or other_detail
        $originalAmount = null;
        
        // If other_detail exists and has original_price, use it
        if ($this->other_detail && isset($this->other_detail['original_price'])) {
            $originalAmount = $this->other_detail['original_price'];            
        } 
        // Otherwise, try to get from plan relationship
        elseif ($this->plan) {
            $originalAmount = $this->plan->amount;
        }
        // Fallback to current amount if no proration
        else {
            $originalAmount = $this->amount;
        }

        // Handle other_detail safely
        $otherDetail = $this->other_detail ?? [];

        if (is_array($otherDetail)) {
            // Keep monetary values precise to 2 decimals.
            if (isset($otherDetail['paid_amount'])) {
                $otherDetail['paid_amount'] = round((float) $otherDetail['paid_amount'], 2);
            }

            if (isset($otherDetail['credit_applied'])) {
                $otherDetail['credit_applied'] = round((float) $otherDetail['credit_applied'], 2);
            }
        }
            
        return [
            'id'                => $this->id,
            'plan_id'           => $this->plan_id,
            'title'             => $this->title,
            'identifier'        => $this->identifier,
            'amount'            => ceil($this->amount),
            'original_amount'   => $originalAmount,
            'type'              => $this->type,
            'txn_id'            => optional($this->payment)->txn_id,
            'status'            => $this->status,
            'start_at'          => $this->start_at,
            'end_at'            => $this->end_at,
            'duration'          => $this->duration,
            'description'       => $this->description,
            'plan_type'         => $this->plan_type,
            'payment_method'    => optional($this->payment)->payment_type,
            'active_in_app_purchase_identifier' => $this->active_in_app_purchase_identifier,
            'plan_limitation'   =>  $this->plan_limitation ?: new \stdClass(), // Return empty object instead of empty array
            'other_detail'      =>  !empty($otherDetail) ? $otherDetail : null // new \stdClass(), // Include proration details
        ];
    }
}
