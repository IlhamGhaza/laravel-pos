<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCharge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ServiceChargeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\ServiceCharge::class);
        try {
            $serviceCharges = ServiceCharge::all();

            return response()->json([
                'message' => 'Service charges retrieved successfully',
                'data' => $serviceCharges
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve service charges.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Models\ServiceCharge::class);
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'rate' => 'required|numeric|min:0|max:100'
            ]);

            $serviceCharge = ServiceCharge::create([
                'name' => $validated['name'],
                'rate' => $validated['rate']
            ]);

            return response()->json([
                'message' => 'Service charge created successfully',
                'data' => $serviceCharge
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Failed to create service charge.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceCharge $serviceCharge): JsonResponse
    {
        $this->authorize('view', $serviceCharge);
        return response()->json([

            'message' => 'Service charge retrieved successfully.',
            'data' => $serviceCharge
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceCharge $serviceCharge): JsonResponse
    {
        $this->authorize('update', $serviceCharge);
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|min:3|max:255',
                'rate' => 'sometimes|required|numeric|min:0|max:100'
            ]);

            $serviceCharge->update($validated);

            return response()->json([

                'message' => 'Service charge updated successfully.',
                'data' => $serviceCharge->fresh()
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([

                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Failed to update service charge.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceCharge $serviceCharge): JsonResponse
    {
        $this->authorize('delete', $serviceCharge);
        try {
            $serviceCharge->delete();

            return response()->json([
                'message' => 'Service charge deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete service charge.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }
}
