<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'shop_name' => optional($this->shop)->shop_name,
            'document_id' => $this->document_id,
            'document_name' => optional($this->document)->name,
            'provider_id' => $this->shop->provider_id,
            'is_verified' => $this->is_verified,
            'shop_document' => getSingleMedia($this, 'shop_document', null),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
