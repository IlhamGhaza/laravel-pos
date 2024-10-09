<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
        // $discounts = Discount::all();
        // foreach ($discounts as $discount) {
        //     // Mengecek apakah diskon telah melewati tanggal berlaku
        //     if (Carbon::now()->gt($discount->expires_at)) {
        //         // Jika sudah lewat, set field 'is_active' ke false
        //         $discount->is_active = false;
        //         $discount->save();
        //     }
        // }

        //or
        $discounts = Discount::where('expired_date', '>=', Carbon::now())->get(); //use this

        return response(['message' => 'success', 'data' => $discounts], 200);
    }

    // store
    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'type' => 'required',
            'value' => 'required',
            'status' => 'nullable|in:active,inactive',
            'expired_date' => 'nullable',
        ]);

        $discounts = Discount::create($request->all());

        return response()->json(['status' => 'success', 'message' => 'Order Created', 'data' => $discounts], 200);
    }

    //update
    public function update(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'type' => 'required',
            'value' => 'required',
            'status' => 'nullable|in:active,inactive',
            'expired_date' => 'nullable',
        ]);

        $discounts = Discount::where('id', $request->id)->first();

        if (! $discounts) {
            return response()->json(['status' => 'error', 'message' => 'Discount not found'], 404);
        }

        $discounts = Discount::where('id', $request->id)->update($request->all());

        return response()->json(['status' => 'success', 'data' => $discounts], 200);
    }

    //show
    public function show(Request $request)
    {
        $discounts = Discount::where('id', $request->id)->first();

        if (! $discounts) {
            return response()->json(['status' => 'error', 'message' => 'Discount not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $discounts], 200);
    }

    //destroy
    public function destroy(string $id)
    {
        $discount = \App\Models\discount::find($id);

        if ($discount) {
            if ($discount->delete()) {
                return response()->json([
                    'success' => true,
                    'message' => 'discount Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to Delete discount',
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'discount Not Found',
            ], 404);
        }
    }
}
