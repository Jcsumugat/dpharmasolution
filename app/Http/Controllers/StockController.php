<?php

namespace App\Http\Controllers;

use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Add stock to a product
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_code' => 'required|string|exists:products,product_code',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->stockService->addStock(
                $request->product_code,
                $request->quantity,
                $request->only([
                    'unit_cost',
                    'batch_number', 
                    'expiration_date',
                    'notes',
                    'reference_number'
                ])
            );

            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove stock from a product
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_code' => 'required|string|exists:products,product_code',
            'quantity' => 'required|integer|min:1',
            'batch_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->stockService->removeStock(
                $request->product_code,
                $request->quantity,
                $request->only(['batch_number', 'notes', 'reference_number'])
            );

            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Adjust stock quantity
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_code' => 'required|string|exists:products,product_code',
            'new_quantity' => 'required|integer|min:0',
            'notes' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->stockService->adjustStock(
                $request->product_code,
                $request->new_quantity,
                $request->notes
            );

            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get stock transaction history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStockHistory(Request $request): JsonResponse
    {
        $request->validate([
            'product_code' => 'required|string|exists:products,product_code',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $history = $this->stockService->getStockHistory(
                $request->product_code,
                $request->get('limit', 50)
            );

            return response()->json([
                'success' => true,
                'data' => $history
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bulk stock update
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStockUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'updates' => 'required|array|min:1|max:100',
            'updates.*.product_code' => 'required|string|exists:products,product_code',
            'updates.*.quantity' => 'required|integer|min:1',
            'updates.*.unit_cost' => 'nullable|numeric|min:0',
            'updates.*.batch_number' => 'nullable|string|max:255',
            'updates.*.expiration_date' => 'nullable|date|after:today',
            'updates.*.notes' => 'nullable|string|max:1000',
            'updates.*.reference_number' => 'nullable|string|max:255',
        ]);

        $results = [];
        $errors = [];

        foreach ($request->updates as $index => $update) {
            try {
                $result = $this->stockService->addStock(
                    $update['product_code'],
                    $update['quantity'],
                    array_filter($update, function($key) {
                        return in_array($key, [
                            'unit_cost', 'batch_number', 'expiration_date', 
                            'notes', 'reference_number'
                        ]);
                    }, ARRAY_FILTER_USE_KEY)
                );
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'product_code' => $update['product_code'],
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => empty($errors),
            'processed' => count($results),
            'errors' => count($errors),
            'results' => $results,
            'error_details' => $errors
        ], empty($errors) ? 200 : 207); // 207 Multi-Status for partial success
    }
}