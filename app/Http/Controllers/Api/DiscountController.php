<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    try {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'type' => 'required',
            'value' => 'required',
            'status' => 'nullable|in:active,inactive',
            'expired_date' => 'nullable|date',
        ]);

        $discounts = Discount::create($validated);

        return response()->json(['status' => 'success', 'message' => 'Discount Created', 'data' => $discounts], 201);
    } catch (ValidationException $e) {
        return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
    } catch (QueryException $e) {
        return response()->json(['status' => 'error', 'message' => 'Database error occurred'], 500);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred'], 500);
    }
}

public function update(Request $request)
{
    try {
        $validated = $request->validate([
            'id' => 'required|exists:discounts,id',
            'name' => 'required',
            'description' => 'nullable',
            'type' => 'required',
            'value' => 'required',
            'status' => 'nullable|in:active,inactive',
            'expired_date' => 'nullable|date',
        ]);

        $discount = Discount::findOrFail($validated['id']);
        $discount->update($validated);

        return response()->json(['status' => 'success', 'message' => 'Discount Updated', 'data' => $discount], 200);
    } catch (ValidationException $e) {
        return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
    } catch (ModelNotFoundException $e) {
        return response()->json(['status' => 'error', 'message' => 'Discount not found'], 404);
    } catch (QueryException $e) {
        return response()->json(['status' => 'error', 'message' => 'Database error occurred'], 500);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred'], 500);
    }
}

public function show($id)
{
    try {
        $discount = Discount::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $discount], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['status' => 'error', 'message' => 'Discount not found'], 404);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred'], 500);
    }
}

public function destroy($id)
{
    try {
        $discount = Discount::findOrFail($id);
        $discount->delete();
        return response()->json(['status' => 'success', 'message' => 'Discount Deleted Successfully'], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['status' => 'error', 'message' => 'Discount not found'], 404);
    } catch (QueryException $e) {
        return response()->json(['status' => 'error', 'message' => 'Database error occurred'], 500);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred'], 500);
    }
}
}
