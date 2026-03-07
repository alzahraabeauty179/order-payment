<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'status' => $this->status,

            'totals' => [
                'subtotal' => round($this->subtotal_amount, 2) .' '. $this->currency,
                'tax' => round($this->tax_amount, 2) .' '. $this->currency,
                'discount' => round($this->discount_amount, 2) .' '. $this->currency,
                'total' => round($this->total_amount, 2) .' '. $this->currency,
            ],

            'meta' => [
                'total_items' => $this->total_items,
                'expires_at' => $this->expires_at,
                'created_at' => $this->created_at,
            ],

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            #TODO: Add payment information when payment implementation is done
            // 'payment' => [
            //     'status' => $this->payment_status,
            //     'amount' => $this->paid_amount,
            //     'method' => $this->payment_method,
            //     'paid_at' => $this->paid_at,
            // ],

            'flags' => [
                'can_pay' => $this->canPay(),
                'can_cancel' => $this->canCancel(),
                'is_expired' => $this->isExpired(),
            ],
        ];
    }
}
