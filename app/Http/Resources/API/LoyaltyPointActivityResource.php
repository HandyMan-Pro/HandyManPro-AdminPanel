<?php

namespace App\Http\Resources\API;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyPointActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get date format from settings
        $site_setup = optional(json_decode(
            Setting::where('type', 'site-setup')->where('key', 'site-setup')->value('value') ?? '{}'
        ));

        $date_format = $site_setup->date_format ?? "F j, Y";
        $time_format = $site_setup->time_format ?? "g:i A";
        $time_zone = $site_setup->time_zone ?? "UTC";

        return [
            'id'            => $this->id,
            'type'          => $this->type,               // earn | redeem | partial_redeem | expire | adjust
            'points'        => $this->points,
            'source'        => $this->source,
            'status'        => $this->earn_type,
            'booking_id'    => $this->related_id,
            'description'   => $this->description,
            'created_at'    => $this->created_at?->setTimezone($time_zone)->format($date_format . ' ' . $time_format),
        ];
    }
}
