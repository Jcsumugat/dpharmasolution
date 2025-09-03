<?php
// app/Http/Controllers/PosController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');

        // Get products with available batches
        $products = Product::whereHas('batches', function ($query) {
            $query->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now());
        })
            ->with(['batches' => function ($query) {
                $query->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now())
                    ->orderBy('expiration_date', 'asc');
            }])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%{$search}%")
                        ->orWhere('brand_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($category, function ($query, $category) {
                return $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            })
            ->orderBy('product_name')
            ->paginate(20);

        // Get categories (assuming you have a categories table)
        $categories = \DB::table('categories')->pluck('name')->sort();

        return view('pos.pos', compact('products', 'categories', 'search', 'category'));
    }

    public function searchProducts(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');

        $products = Product::whereHas('batches', function ($query) {
            $query->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now());
        })
            ->with(['batches' => function ($query) {
                $query->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now())
                    ->orderBy('expiration_date', 'asc');
            }])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%{$search}%")
                        ->orWhere('brand_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($category, function ($query, $category) {
                return $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            })
            ->orderBy('product_name')
            ->limit(20)
            ->get();

        // Add calculated fields for frontend
        $products = $products->map(function ($product) {
            $product->total_stock = $product->batches->sum('quantity_remaining');
            $product->unit_price = $product->batches->first()->sale_price ?? 0;
            return $product;
        });

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function getProduct($id)
    {
        $product = Product::with(['batches' => function ($query) {
            $query->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now())
                ->orderBy('expiration_date', 'asc');
        }])->findOrFail($id);

        if ($product->batches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Product is out of stock'
            ]);
        }

        // Get the batch with earliest expiration date (FIFO)
        $batch = $product->batches->first();

        $product->total_stock = $product->batches->sum('quantity_remaining');
        $product->unit_price = $batch->sale_price;
        $product->batch_id = $batch->id;

        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }

    public function processTransaction(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,gcash',
            'customer_name' => 'nullable|string|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $itemsData = [];

            // Validate items and calculate totals
            foreach ($request->items as $item) {
                $product = Product::with(['batches' => function ($query) {
                    $query->where('quantity_remaining', '>', 0)
                        ->where('expiration_date', '>', now())
                        ->orderBy('expiration_date', 'asc');
                }])->findOrFail($item['product_id']);

                if ($product->batches->isEmpty()) {
                    throw new \Exception("Product {$product->product_name} is out of stock");
                }

                $totalAvailable = $product->batches->sum('quantity_remaining');

                if ($totalAvailable < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->product_name}. Available: {$totalAvailable}");
                }

                // Get price from first available batch (FIFO)
                $batch = $product->batches->first();
                $itemTotal = $batch->sale_price * $item['quantity'];
                $subtotal += $itemTotal;

                $itemsData[] = [
                    'product' => $product,
                    'batch' => $batch,
                    'quantity' => $item['quantity'],
                    'unit_price' => $batch->sale_price,
                    'total_price' => $itemTotal
                ];
            }

            $discountAmount = $request->discount_amount ?? 0;
            $taxAmount = 0; // You can implement tax calculation here if needed
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            if ($request->amount_paid < $totalAmount) {
                throw new \Exception('Insufficient payment amount');
            }

            $changeAmount = $request->amount_paid - $totalAmount;

            // Create transaction
            $transaction = PosTransaction::create([
                'customer_type' => 'walk_in',
                'customer_name' => $request->customer_name,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $request->amount_paid,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'processed_by' => Auth::id()
            ]);

            // Create transaction items and update stock
            foreach ($itemsData as $itemData) {
                PosTransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $itemData['product']->id,
                    'product_name' => $itemData['product']->product_name,
                    'brand_name' => $itemData['product']->brand_name,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price']
                ]);

                // Update batch stock (FIFO - First In, First Out)
                $remainingQuantity = $itemData['quantity'];

                foreach ($itemData['product']->batches as $batch) {
                    if ($remainingQuantity <= 0) break;

                    if ($batch->quantity_remaining >= $remainingQuantity) {
                        // This batch can fulfill the remaining quantity
                        $batch->decrement('quantity_remaining', $remainingQuantity);
                        $remainingQuantity = 0;
                    } else {
                        // Use all of this batch and continue to next
                        $remainingQuantity -= $batch->quantity_remaining;
                        $batch->update(['quantity_remaining' => 0]);
                    }
                }

                // Update the product's stock_quantity after deducting from batches
                $itemData['product']->decrement('stock_quantity', $itemData['quantity']);

                // Optional: Create stock movement record for tracking
                StockMovement::create([
                    'product_id' => $itemData['product']->id,
                    'type' => 'sale',
                    'quantity' => -$itemData['quantity'],
                    'reference_id' => $transaction->id,
                    'reference_type' => 'pos_transaction',
                    'notes' => "POS sale - Transaction #{$transaction->id}"
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction completed successfully',
                'transaction' => $transaction->load('items'),
                'change_amount' => $changeAmount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function cancelTransaction()
    {
        // Clear any session data if needed
        return response()->json([
            'success' => true,
            'message' => 'Transaction cancelled'
        ]);
    }

    public function getReceipt($transactionId)
    {
        $transaction = PosTransaction::with('items')->findOrFail($transactionId);

        return response()->json([
            'success' => true,
            'transaction' => $transaction
        ]);
    }
}
