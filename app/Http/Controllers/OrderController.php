<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'shippingDetails' => 'required|array',
                'shippingDetails.fullName' => 'required|string',
                'shippingDetails.address' => 'required|string',
                'shippingDetails.city' => 'required|string',
                'shippingDetails.postalCode' => 'required|string',
                'shippingDetails.phone' => 'required|string',
                'paymentMethod' => 'required|string|in:cod',
                'totalAmount' => 'required|numeric|min:0',
            ]);

            // Create the order
            $order = Order::create([
                'user_id' => auth()->id(),
                'items' => $request->items,
                'shipping_details' => $request->shippingDetails,
                'payment_method' => $request->paymentMethod,
                'total_amount' => $request->totalAmount,
                'status' => 'pending'
            ]);

            // Update product quantities
            foreach ($request->items as $item) {
                $product = \App\Models\Product::find($item['id']);
                if ($product->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->description}");
                }
                $product->quantity -= $item['quantity'];
                $product->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 