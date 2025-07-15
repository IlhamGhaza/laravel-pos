<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    /**
     * Default number of items per page for pagination
     */
    protected const PER_PAGE = 15;

    /**
     * Display a listing of the suppliers with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Supplier::class);
        try {
            $perPage = $request->input('per_page', self::PER_PAGE);
            $sortField = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $search = $request->input('search');

            $query = Supplier::query();

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $validSortFields = ['name', 'company_name', 'created_at', 'status'];
            $sortField = in_array($sortField, $validSortFields) ? $sortField : 'name';
            $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

            $suppliers = $query->orderBy($sortField, $sortOrder)
                             ->paginate($perPage);

            return $this->buildResponse([
                'success' => true,
                'data' => $suppliers->items(),
                'meta' => [
                    'current_page' => $suppliers->currentPage(),
                    'from' => $suppliers->firstItem(),
                    'last_page' => $suppliers->lastPage(),
                    'per_page' => $suppliers->perPage(),
                    'to' => $suppliers->lastItem(),
                    'total' => $suppliers->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch suppliers: ' . $e->getMessage());

            return $this->buildResponse([
                'success' => false,
                'message' => 'Gagal mengambil data supplier',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created supplier in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\Supplier::class);
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255|unique:suppliers,email',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
                'payment_terms' => 'nullable|string|max:100',
                'credit_days' => 'nullable|integer|min:0',
                'status' => 'required|in:' . implode(',', [
                    Supplier::STATUS_ACTIVE,
                    Supplier::STATUS_INACTIVE,
                    Supplier::STATUS_SUSPENDED,
                ]),
                'notes' => 'nullable|string',
            ], $this->getValidationMessages());

            if ($validator->fails()) {
                return $this->buildResponse([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $supplier = Supplier::create($validator->validated());

            DB::commit();

            return $this->buildResponse([
                'success' => true,
                'message' => 'Supplier berhasil ditambahkan',
                'data' => $supplier
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create supplier: ' . $e->getMessage());

            return $this->buildResponse([
                'success' => false,
                'message' => 'Gagal menambahkan supplier',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified supplier.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);
        try {
            return $this->buildResponse([
                'success' => true,
                'data' => $supplier->load(['purchaseOrders', 'products'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch supplier: ' . $e->getMessage());

            return $this->buildResponse([
                'success' => false,
                'message' => 'Gagal mengambil detail supplier',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified supplier in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
                'payment_terms' => 'nullable|string|max:100',
                'credit_days' => 'nullable|integer|min:0',
                'status' => 'sometimes|required|in:' . implode(',', [
                    Supplier::STATUS_ACTIVE,
                    Supplier::STATUS_INACTIVE,
                    Supplier::STATUS_SUSPENDED,
                ]),
                'notes' => 'nullable|string',
            ], $this->getValidationMessages());

            if ($validator->fails()) {
                return $this->buildResponse([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $supplier->update($validator->validated());

            DB::commit();

            return $this->buildResponse([
                'success' => true,
                'message' => 'Data supplier berhasil diperbarui',
                'data' => $supplier->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update supplier: ' . $e->getMessage());

            return $this->buildResponse([
                'success' => false,
                'message' => 'Gagal memperbarui data supplier',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified supplier from storage (soft delete).
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);
        try {
            DB::beginTransaction();

            // Check if supplier has related records before deleting
            if ($supplier->purchaseOrders()->exists()) {
                return $this->buildResponse([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus supplier yang memiliki riwayat pembelian'
                ], 422);
            }

            $supplier->delete();

            DB::commit();

            return $this->buildResponse([
                'success' => true,
                'message' => 'Supplier berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete supplier: ' . $e->getMessage());

            return $this->buildResponse([
                'success' => false,
                'message' => 'Gagal menghapus supplier',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get the validation messages for supplier operations.
     *
     * @return array
     */
    protected function getValidationMessages()
    {
        return [
            'name.required' => 'Nama supplier wajib diisi',
            'name.max' => 'Nama supplier maksimal 255 karakter',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'phone_number.max' => 'Nomor telepon maksimal 20 karakter',
            'company_name.max' => 'Nama perusahaan maksimal 255 karakter',
            'tax_number.max' => 'Nomor NPWP maksimal 50 karakter',
            'payment_terms.max' => 'Syarat pembayaran maksimal 100 karakter',
            'credit_days.integer' => 'Jangka waktu kredit harus berupa angka',
            'credit_days.min' => 'Jangka waktu kredit minimal 0',
            'status.required' => 'Status supplier wajib dipilih',
            'status.in' => 'Status tidak valid',
        ];
    }

    /**
     * Build a consistent JSON response.
     *
     * @param  array  $data
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildResponse(array $data, int $statusCode = 200)
    {
        return response()->json($data, $statusCode);
    }
}
