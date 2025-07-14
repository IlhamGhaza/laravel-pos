<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\ValidDay;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    public function index()
    {
        try {
            // Ambil diskon aktif dengan relasi validDays untuk performa
            $discounts = Discount::with('validDays')
                ->activeForSync()
                ->get();

            // Transform data untuk response yang lebih clean
            $transformedDiscounts = $discounts->map(function ($discount) {
                $data = $discount->toArray();

                // Tambahkan valid_days sebagai array sederhana
                $data['valid_days'] = $discount->validDays->pluck('day_of_week')->toArray();

                // Hapus relasi dari response untuk mengurangi payload
                unset($data['valid_days_relation']);

                return $data;
            });

            return response()->json([
                'message' => 'success',
                'data' => $transformedDiscounts,
                'sync_time' => now(),
                'total' => $transformedDiscounts->count()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch discounts'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:fixed,percentage,buy_x_get_y,quantity_based,bulk_discount',
                'value' => 'required|numeric|min:0',
                'min_quantity' => 'nullable|numeric|min:0',
                'max_quantity' => 'nullable|numeric|min:0|gte:min_quantity',
                'min_amount' => 'nullable|numeric|min:0',
                'buy_quantity' => 'nullable|integer|min:1',
                'get_quantity' => 'nullable|integer|min:1',
                'quantity_tiers' => 'nullable|array',
                'quantity_tiers.*.min_qty' => 'required_with:quantity_tiers|numeric|min:0',
                'quantity_tiers.*.max_qty' => 'nullable|numeric|min:0',
                'quantity_tiers.*.discount' => 'required_with:quantity_tiers|numeric|min:0',
                'apply_to' => 'nullable|in:all,category,product',
                'applicable_items' => 'nullable|array',
                'applicable_items.*' => 'integer|min:1',
                'customer_type' => 'nullable|in:all,retail,wholesale,member',
                'combinable' => 'nullable|boolean',
                'usage_limit' => 'nullable|integer|min:1',
                'status' => 'nullable|in:active,inactive',
                'start_date' => 'nullable|date|after_or_equal:today',
                'expired_date' => 'nullable|date|after:start_date',
                'start_time' => 'nullable|date_format:H:i:s',
                'end_time' => 'nullable|date_format:H:i:s|after:start_time',
                'valid_days' => 'nullable|array',
                'valid_days.*' => 'integer|min:1|max:7'
            ]);

            DB::beginTransaction();

            // Set default values

            $validated['combinable'] = $validated['combinable'] ?? false;
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['usage_count'] = 0;

            // Hapus valid_days dari data utama
            $validDays = $validated['valid_days'] ?? [];
            unset($validated['valid_days']);

            // Create discount
            $discount = Discount::create($validated);

            // Create valid days jika ada
            if (!empty($validDays)) {
                foreach (array_unique($validDays) as $day) {
                    ValidDay::create([
                        'discount_id' => $discount->id,
                        'day_of_week' => $day
                    ]);
                }
            }

            // Load relasi untuk response
            $discount->load('validDays');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Discount created successfully',
                'data' => $discount
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Database error occurred'
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    public function update(Request $request, Discount $discount) // Use Route Model Binding
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:fixed,percentage,buy_x_get_y,quantity_based,bulk_discount',
                'value' => 'required|numeric|min:0',
                'min_quantity' => 'nullable|numeric|min:0',
                'max_quantity' => 'nullable|numeric|min:0|gte:min_quantity',
                'min_amount' => 'nullable|numeric|min:0',
                'buy_quantity' => 'nullable|integer|min:1',
                'get_quantity' => 'nullable|integer|min:1',
                'quantity_tiers' => 'nullable|array',
                'quantity_tiers.*.min_qty' => 'required_with:quantity_tiers|numeric|min:0',
                'quantity_tiers.*.max_qty' => 'nullable|numeric|min:0',
                'quantity_tiers.*.discount' => 'required_with:quantity_tiers|numeric|min:0',
                'apply_to' => 'required|in:all,category,product',
                'applicable_items' => 'nullable|array',
                'applicable_items.*' => 'integer|min:1',
                'customer_type' => 'required|in:all,retail,wholesale,member',
                'combinable' => 'nullable|boolean',
                'usage_limit' => 'nullable|integer|min:1',
                'status' => 'nullable|in:active,inactive',
                'start_date' => 'nullable|date',
                'expired_date' => 'nullable|date|after:start_date',
                'start_time' => 'nullable|date_format:H:i:s',
                'end_time' => 'nullable|date_format:H:i:s|after:start_time',
                'valid_days' => 'nullable|array',
                'valid_days.*' => 'integer|min:1|max:7'
            ]);

            DB::beginTransaction();

            // Ambil valid_days sebelum update
            $validDays = $validated['valid_days'] ?? [];
            unset($validated['valid_days']);

            // Update discount
            $discount->update($validated);

            // Update valid days - hapus yang lama, buat yang baru
            $discount->validDays()->delete();

            if (!empty($validDays)) {
                foreach (array_unique($validDays) as $day) {
                    ValidDay::create([
                        'discount_id' => $discount->id,
                        'day_of_week' => $day
                    ]);
                }
            }

            // Load relasi untuk response
            $discount->load('validDays');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Discount updated successfully',
                'data' => $discount
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Discount not found'
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Database error occurred'
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    public function show(Discount $discount) // Use Route Model Binding
    {
        try {
            $discount->load('validDays');

            // Transform response
            $data = $discount->toArray();
            $data['valid_days'] = $discount->validDays->pluck('day_of_week')->toArray();
            unset($data['valid_days_relation']);

            return response()->json([
                'status' => 'success',
                'data' => $data
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Discount not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    public function destroy(Discount $discount) // Use Route Model Binding
    {
        try {
            DB::beginTransaction();


            // Hapus valid days dulu (cascade delete seharusnya handle ini, tapi untuk memastikan)
            $discount->validDays()->delete();

            // Soft delete discount
            $discount->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Discount deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Discount not found'
            ], 404);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Database error occurred'
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Get discounts valid for today
     */
    public function todayDiscounts()
    {
        try {
            $discounts = Discount::with('validDays')
                ->validToday()
                ->where('status', 'active')
                ->get();

            $transformedDiscounts = $discounts->map(function ($discount) {
                $data = $discount->toArray();
                $data['valid_days'] = $discount->validDays->pluck('day_of_week')->toArray();
                unset($data['valid_days_relation']);
                return $data;
            });

            return response()->json([
                'status' => 'success',
                'data' => $transformedDiscounts,
                'today' => now()->dayOfWeek,
                'total' => $transformedDiscounts->count()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch today discounts'
            ], 500);
        }
    }

    /**
     * Sync endpoint untuk mobile (optimized)
     */
    public function sync()
    {
        try {
            $discounts = Discount::with('validDays')
                ->activeForSync()
                ->get();

            // Transform untuk mobile dengan struktur yang lebih simple
            $mobileData = $discounts->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'description' => $discount->description,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'min_quantity' => $discount->min_quantity,
                    'max_quantity' => $discount->max_quantity,
                    'min_amount' => $discount->min_amount,
                    'buy_quantity' => $discount->buy_quantity,
                    'get_quantity' => $discount->get_quantity,
                    'quantity_tiers' => $discount->quantity_tiers,
                    'apply_to' => $discount->apply_to,
                    'applicable_items' => $discount->applicable_items,
                    'customer_type' => $discount->customer_type,
                    'combinable' => $discount->combinable,
                    'usage_limit' => $discount->usage_limit,
                    'usage_count' => $discount->usage_count,
                    'start_time' => $discount->start_time ? Carbon::parse($discount->start_time)->format('H:i:s') : null,
                    'end_time' => $discount->end_time ? Carbon::parse($discount->end_time)->format('H:i:s') : null,
                    'valid_days' => $discount->validDays->pluck('day_of_week')->toArray(),
                    'start_date' => $discount->start_date ? Carbon::parse($discount->start_date)->format('Y-m-d') : null,
                    'expired_date' => $discount->expired_date ? Carbon::parse($discount->expired_date)->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $mobileData,
                'sync_time' => now()->toISOString(),
                'next_sync' => now()->addWeek()->toISOString(),
                'total' => $mobileData->count()
            ], 200);
        } catch (Exception $e) {
            // Log error yang detail untuk debugging
            Log::error('Discount Sync Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed. Please check server logs for details.' // Pesan bisa lebih spesifik jika di environment dev
            ], 500);
        }
    }
}
