<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Setting;

class ShopHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }
        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $sitesetupValue = json_decode(optional($sitesetup)->value ?? '{}');
        $targetTimezone = isset($sitesetupValue->time_zone) ? trim((string) $sitesetupValue->time_zone) : 'UTC';
        $timeFormat = $sitesetupValue->time_format ?? 'H:i';
        $data = [
            'id' => $this->id,
            'day' => $this->day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_holiday' => $this->is_holiday ? true : false,
            'breaks' => $this->breaks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
        return $data;
    }   
}