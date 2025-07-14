<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            'quantity' => (int) $this->quantity,
            'price' => (float) $this->price,
            'total_price' => (float) $this->total_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
