<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    //store order and order item
    public function store(Request $request)
    {
        try {
            // Validation
            $validatedData = $request->validate([
                'transaction_time' => 'required|date',
                'payment_amount' => 'required|numeric|min:0',
                'sub_total' => 'required|numeric|min:0',
                'tax' => 'required|numeric|min:0',
                'discount' => 'required|numeric|min:0',
                'service_charge' => 'required|numeric|min:0',
                'kasir_id' => 'required|exists:users,id',
                'total_price' => 'required|numeric|min:0',
                'total_item' => 'required|integer|min:1',
                'order_items' => 'required|array|min:1',
                'order_items.*.product_id' => 'required|exists:products,id',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.total_price' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
            ]);

            DB::beginTransaction();

            // Create order
            $order = \App\Models\Order::create($validatedData);

            // Create order items
            foreach ($validatedData['order_items'] as $item) {
                $product = \App\Models\Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product ID: {$item['product_id']}");
                }

                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'total_price' => $item['total_price'],
                ]);

                // Update product stock
                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order Created Successfully',
                'data' => $order->load('orderItems.product'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order Creation Failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
