<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //store order and order item
    public function store(Request $request)
    {
        //validate
        $request->validate([
            'transaction_time' => 'required',
            'payment_amount' => 'required', //from this line
            'sub_total'=> 'required',
            'tax'=> 'required',
            'discount'=> 'required',
            'service_charge'=> 'required', //end
            'kasir_id' => 'required|exists:users,id',
            'total_price' => 'required|numeric',
            'total_item' => 'required|numeric',
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.quantity' => 'required|numeric',
            'order_items.*.total_price' => 'required|numeric',
        ]);

        //create order
        $order = \App\Models\Order::create([
            'transaction_time' => $request->transaction_time,
            'payment_amount' => $request->payment_amount, //from this line
            'sub_total' => $request->sub_total,
            'tax' => $request->tax,
            'discount' => $request->discount,
            'service_charge' => $request->service_charge, //end
            'kasir_id' => $request->kasir_id,
            'total_price' => $request->total_price,
            'total_item' => $request->total_item,
            'payment_method' => $request->payment_method,
        ]);

        //create order item
        foreach ($request->order_items as $item) {
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
            ]);
        }

        //response
        return response()->json([
            'success' => true,
            'message' => 'Order Created',
            'data' => $order
        ], 201);
    }
}
