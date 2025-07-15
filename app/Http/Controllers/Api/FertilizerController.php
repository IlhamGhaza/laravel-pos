<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FertilizerController extends Controller
{
    protected $fertilizerService;

    public function __construct(FertilizerService $fertilizerService)
    {
        $this->fertilizerService = $fertilizerService;
    }

    /**
     * Check for expired or near-expiry products
     */
    public function checkExpiry(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        $daysBefore = $request->input('days_before', 30);

        $result = $this->fertilizerService->checkProductExpiry($daysBefore);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Convert between units
     */
    public function convertUnit(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric|min:0',
            'from_unit' => 'required|string',
            'to_unit' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->fertilizerService->convertUnit(
                $request->value,
                $request->from_unit,
                $request->to_unit
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'value' => $result,
                    'unit' => $request->to_unit,
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get fertilizer recommendations
     */
    public function getRecommendations(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        $validator = Validator::make($request->all(), [
            'plant_type' => 'required|string',
            'soil_type' => 'nullable|string',
            'growth_stage' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $recommendations = $this->fertilizerService->getRecommendations(
            $request->plant_type,
            $request->soil_type,
            $request->growth_stage
        );

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * Calculate fertilizer requirement
     */
    public function calculateRequirement(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'area' => 'required|numeric|min:0.01',
            'area_unit' => 'required|in:ha,m2,are,km2,hectare',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->fertilizerService->calculateRequirement(
                $request->product_id,
                $request->area,
                $request->area_unit
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available units for conversion
     */
    public function getAvailableUnits()
    {
        $this->authorize('viewAny', Product::class);
        return response()->json([
            'success' => true,
            'data' => [
                ['code' => 'kg', 'name' => 'Kilogram'],
                ['code' => 'gr', 'name' => 'Gram'],
                ['code' => 'ons', 'name' => 'Ons'],
                ['code' => 'kwintal', 'name' => 'Kwintal'],
                ['code' => 'ton', 'name' => 'Ton'],
                ['code' => 'sak', 'name' => 'Sak'],
                ['code' => 'karung', 'name' => 'Karung'],
            ],
        ]);
    }

    /**
     * Get available area units
     */
    public function getAreaUnits()
    {
        $this->authorize('viewAny', Product::class);
        return response()->json([
            'success' => true,
            'data' => [
                ['code' => 'ha', 'name' => 'Hektar'],
                ['code' => 'm2', 'name' => 'Meter Persegi'],
                ['code' => 'are', 'name' => 'Are'],
                ['code' => 'km2', 'name' => 'Kilometer Persegi'],
                ['code' => 'hectare', 'name' => 'Hektar (hectare)'],
            ],
        ]);
    }
}
