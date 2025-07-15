<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    // Catatan: Asumsikan CustomerPolicy akan dibuat untuk otorisasi
    // dengan struktur yang mirip dengan ProductPolicy.

    public function index()
    {
        $this->authorize('viewAny', Customer::class);
        return Customer::all();
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:customers,email',
                'phone_number' => 'nullable|string|max:20|unique:customers,phone_number',
                'address' => 'nullable|string',
            ], [
                'phone_number.unique' => 'Nomor telepon sudah digunakan oleh pelanggan lain.',
                'email.unique' => 'Email sudah digunakan oleh pelanggan lain.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            $customer = Customer::create($validator->validated());
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pelanggan berhasil ditambahkan',
                'data' => $customer
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menambahkan pelanggan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan pelanggan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        return $customer;
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|unique:customers,email,' . $customer->id,
                'phone_number' => 'nullable|string|max:20|unique:customers,phone_number,' . $customer->id,
                'address' => 'nullable|string',
            ], [
                'phone_number.unique' => 'Nomor telepon sudah digunakan oleh pelanggan lain.',
                'email.unique' => 'Email sudah digunakan oleh pelanggan lain.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            $customer->update($validator->validated());
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pelanggan berhasil diperbarui',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui pelanggan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui pelanggan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        try {
            // Begin database transaction
            DB::beginTransaction();

            // Delete the customer
            $customer->delete();

            // Commit the transaction
            DB::commit();

            // Return success response with 200 OK status code
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
                'data' => null
            ]);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            // Log the error
            Log::error('Failed to delete customer: ' . $e->getMessage());

            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
