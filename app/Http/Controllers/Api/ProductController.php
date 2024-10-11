<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //all products
        $products = \App\Models\Product::where('stock', '>', 0)->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Product',
            'data' => $products,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|min:3',
                'price' => 'required|integer',
                'stock' => 'required|integer',
                'category_id' => 'required|exists:categories,id',
                'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
            ]);

            $filename = time().'.'.$request->image->extension();
            $request->image->storeAs('public/products', $filename);

            $category = \App\Models\Category::findOrFail($request->category_id);

            $product = \App\Models\Product::create([
                'name' => $request->name,
                'price' => (int) $request->price,
                'stock' => (int) $request->stock,
                'category_id' => $request->category_id,
                'category' => $category->name,
                'image' => $filename,
                'is_favorite' => $request->is_favorite ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product Created Successfully',
                'data' => $product,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category Not Found',
            ], 404);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error Uploading Image',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        // $product = \App\Models\Product::find($id);
        // $product->delete();
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Product Deleted'
        // ], 200);

        $product = \App\Models\Product::find($id);

        if ($product) {
            if ($product->delete()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to Delete Product',
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product Not Found',
            ], 404);
        }
    }
}
