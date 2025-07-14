<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Basic order information
            'id' => $this->id,
            'order_number' => $this->midtrans_order_id,
            'status' => $this->status,
            'order_type' => $this->order_type,
            'transaction_time' => $this->transaction_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),

            // Staff information
            'kasir_id' => $this->kasir_id,
            'kasir_name' => $this->kasir_name,

            // Customer information
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_order_notes' => $this->customer_order_notes,

            // Pricing information
            'sub_total' => (float) $this->sub_total,
            'total_price' => (float) $this->total_price,
            'total_item' => (int) $this->total_item,

            // Tax information
            'tax_id' => $this->tax_id,
            'tax_rate' => $this->tax_rate ? (float) $this->tax_rate : null,
            'tax_amount' => (float) $this->tax_amount,

            // Service charge information
            'service_charge_id' => $this->service_charge_id,
            'service_charge_rate' => $this->service_charge_rate ? (float) $this->service_charge_rate : null,
            'service_charge' => (float) $this->service_charge,

            // Discount information
            'discount_id' => $this->discount_id,
            'discount_amount' => (float) $this->discount_amount,
            'discount_details' => $this->discount_details ?? null,

            // Payment information
            'payment_method' => $this->payment_method,
            'payment_amount' => (float) $this->payment_amount,
            'change_amount' => (float) $this->change_amount,
            'paid_at' => $this->paid_at,

            // Midtrans integration
            'midtrans_transaction_id' => $this->midtrans_transaction_id,
            'midtrans_order_id' => $this->midtrans_order_id,
            'payment_gateway_response' => $this->payment_gateway_response,

            // Mobile sync information
            'is_synced_from_mobile' => (bool) $this->is_synced_from_mobile,
            'mobile_sync_validation_status' => $this->mobile_sync_validation_status,
            'mobile_sync_notes' => $this->mobile_sync_notes,
            'mobile_synced_at' => $this->mobile_synced_at,

            // Relationships
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems'))
        ];
    }
}
