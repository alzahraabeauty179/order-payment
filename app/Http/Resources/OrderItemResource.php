<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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

            'product' => [
                'id' => $this->product_id,
                'name' => $this->product_name_snapshot,
            ],

            'pricing' => [
                'unit_price' => $this->unit_price_snapshot,
                'quantity' => $this->quantity,
                'total_price' => $this->total_price_snapshot,
            ],
        ];
    }
}
