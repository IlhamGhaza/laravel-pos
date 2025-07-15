<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Tax::class);
        try {
            $taxes = Tax::all();

            return response()->json([

                'message' => 'Taxes retrieved successfully.',
                'data' => $taxes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Failed to retrieve taxes.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Tax::class);
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'rate' => 'required|numeric|min:0|max:100',
            ]);

            $tax = Tax::create([
                'name' => $validated['name'],
                'rate' => $validated['rate'],
            ]);

            return response()->json([

                'message' => 'Tax created successfully.',
                'data' => $tax
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([

                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Failed to create tax.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax): JsonResponse
    {
        $this->authorize('view', $tax);


        return response()->json([

            'message' => 'Tax retrieved successfully.',
            'data' => $tax
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tax $tax): JsonResponse
    {
        $this->authorize('update', $tax);
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|min:3|max:255',
                'rate' => 'sometimes|required|numeric|min:0|max:100',
            ]);

            $tax->update($validated);

            return response()->json([

                'message' => 'Tax updated successfully.',
                'data' => $tax->fresh()
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([

                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Failed to update tax.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax): JsonResponse
    {
        $this->authorize('delete', $tax);
        try {
            $tax->delete();

            return response()->json([

                'message' => 'Tax deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([

                'message' => 'Failed to delete tax.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
    }
}
