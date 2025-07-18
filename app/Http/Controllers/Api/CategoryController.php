<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Category::class);
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string',
            ], [
                'name.unique' => 'Nama kategori sudah digunakan',
                'name.required' => 'Nama kategori wajib diisi',
                'name.max' => 'Nama kategori maksimal 255 karakter'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = Category::create($request->only(['name', 'description']));

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            Log::error('Gagal menambahkan kategori: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan kategori',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $this->authorize('view', Category::class);
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
        $this->authorize('view', $category);

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('update', Category::class);
        try {
            $category = Category::find($id);
            $response = [];

            if (!$category) {
                $response = [
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan'
                ];
                return response()->json($response, 404);
            }
            $this->authorize('update', $category);

            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')->ignore($category->id)
                ],
                'description' => 'nullable|string',
            ], [
                'name.unique' => 'Nama kategori sudah digunakan',
                'name.required' => 'Nama kategori wajib diisi',
                'name.max' => 'Nama kategori maksimal 255 karakter'
            ]);

            if ($validator->fails()) {
                $response = [
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ];
                return response()->json($response, 422);
            }

            $category->update($request->only(['name', 'description']));

            $response = [
                'success' => true,
                'message' => 'Kategori berhasil diperbarui',
                'data' => $category
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Gagal memperbarui kategori: ' . $e->getMessage());
            $response = [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui kategori',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('delete', Category::class);
        try {
            $category = Category::find($id);
            $response = [];

            if (!$category) {
                $response = [
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan'
                ];
                return response()->json($response, 404);
            }
            $this->authorize('delete', $category);

            // Cek apakah kategori memiliki produk
            if ($category->products()->exists()) {
                $response = [
                    'success' => false,
                    'message' => 'Tidak dapat menghapus kategori karena memiliki produk terkait'
                ];
                return response()->json($response, 422);
            }

            $category->delete();

            $response = [
                'success' => true,
                'message' => 'Kategori berhasil dihapus',
                'data' => null
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Gagal menghapus kategori: ' . $e->getMessage());
            $response = [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus kategori',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
            return response()->json($response, 500);
        }
    }
}
