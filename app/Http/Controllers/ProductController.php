<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::all();
            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Received product data:', $request->all());

            $validator = Validator::make($request->all(), [
                'barcode' => 'required|string|unique:products',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:0',
                'category' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->except('image');

            if ($request->hasFile('image')) {
                \Log::info('Processing image upload');
                
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                $path = $file->storeAs('products', $filename, 'public');
                \Log::info('Image stored at: ' . $path);
                
                $data['image_url'] = url('storage/' . $path);
                \Log::info('Image URL set to: ' . $data['image_url']);
            }

            $product = Product::create($data);
            \Log::info('Product created:', $product->toArray());

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], 403);
            }

            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            \Log::info('Updating product ' . $id, $request->all());

            $request->validate([
                'barcode' => 'required|string|unique:products,barcode,' . $id,
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:0',
                'category' => 'required|string',
            ]);

            $product = Product::findOrFail($id);
            $data = $request->except('image');

            if ($request->hasFile('image')) {
                \Log::info('Processing image update');
                
                if ($product->image_url) {
                    $oldPath = str_replace(url('storage/'), '', $product->image_url);
                    Storage::disk('public')->delete($oldPath);
                    \Log::info('Deleted old image: ' . $oldPath);
                }

                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                $path = $file->storeAs('products', $filename, 'public');
                \Log::info('New image stored at: ' . $path);
                
                $data['image_url'] = url('storage/' . $path);
                \Log::info('New image URL set to: ' . $data['image_url']);
            }

            $product->update($data);
            \Log::info('Product updated:', $product->toArray());

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 