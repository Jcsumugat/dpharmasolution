<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Add stock to a product
     *
     * @param string $productCode
     * @param int $quantity
     * @param array $additionalData
     * @return array
     * @throws Exception
     */
    public function addStock(string $productCode, int $quantity, array $additionalData = []): array
    {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than 0');
        }

        return DB::transaction(function () use ($productCode, $quantity, $additionalData) {
            // Find the product
            $product = Product::where('product_code', $productCode)->first();
            
            if (!$product) {
                throw new Exception("Product with code {$productCode} not found");
            }

            $previousStock = $product->stock_quantity;
            $newStock = $previousStock + $quantity;

            // Update product stock
            $product->update([
                'stock_quantity' => $newStock,
                'updated_at' => now()
            ]);

            // Create stock transaction record
            $transaction = StockTransaction::create([
                'product_code' => $productCode,
                'transaction_type' => 'stock_in',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'unit_cost' => $additionalData['unit_cost'] ?? null,
                'batch_number' => $additionalData['batch_number'] ?? null,
                'expiration_date' => $additionalData['expiration_date'] ?? null,
                'notes' => $additionalData['notes'] ?? null,
                'reference_number' => $additionalData['reference_number'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => "Successfully added {$quantity} units to {$product->product_name}",
                'product' => $product->fresh(),
                'transaction' => $transaction,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock
            ];
        });
    }

    /**
     * Remove stock from a product
     *
     * @param string $productCode
     * @param int $quantity
     * @param array $additionalData
     * @return array
     * @throws Exception
     */
    public function removeStock(string $productCode, int $quantity, array $additionalData = []): array
    {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than 0');
        }

        return DB::transaction(function () use ($productCode, $quantity, $additionalData) {
            $product = Product::where('product_code', $productCode)->first();
            
            if (!$product) {
                throw new Exception("Product with code {$productCode} not found");
            }

            $previousStock = $product->stock_quantity;
            
            if ($previousStock < $quantity) {
                throw new Exception("Insufficient stock. Available: {$previousStock}, Requested: {$quantity}");
            }

            $newStock = $previousStock - $quantity;

            // Update product stock
            $product->update([
                'stock_quantity' => $newStock,
                'updated_at' => now()
            ]);

            // Create stock transaction record
            $transaction = StockTransaction::create([
                'product_code' => $productCode,
                'transaction_type' => 'stock_out',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'batch_number' => $additionalData['batch_number'] ?? null,
                'notes' => $additionalData['notes'] ?? null,
                'reference_number' => $additionalData['reference_number'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => "Successfully removed {$quantity} units from {$product->product_name}",
                'product' => $product->fresh(),
                'transaction' => $transaction,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock
            ];
        });
    }

    /**
     * Adjust stock (for corrections)
     *
     * @param string $productCode
     * @param int $newQuantity
     * @param string $notes
     * @return array
     * @throws Exception
     */
    public function adjustStock(string $productCode, int $newQuantity, string $notes = ''): array
    {
        if ($newQuantity < 0) {
            throw new Exception('Stock quantity cannot be negative');
        }

        return DB::transaction(function () use ($productCode, $newQuantity, $notes) {
            $product = Product::where('product_code', $productCode)->first();
            
            if (!$product) {
                throw new Exception("Product with code {$productCode} not found");
            }

            $previousStock = $product->stock_quantity;
            $difference = $newQuantity - $previousStock;

            // Update product stock
            $product->update([
                'stock_quantity' => $newQuantity,
                'updated_at' => now()
            ]);

            // Create stock transaction record
            $transaction = StockTransaction::create([
                'product_code' => $productCode,
                'transaction_type' => 'adjustment',
                'quantity' => $difference,
                'previous_stock' => $previousStock,
                'new_stock' => $newQuantity,
                'notes' => $notes ?: "Stock adjustment: {$previousStock} → {$newQuantity}",
            ]);

            return [
                'success' => true,
                'message' => "Stock adjusted from {$previousStock} to {$newQuantity}",
                'product' => $product->fresh(),
                'transaction' => $transaction,
                'previous_stock' => $previousStock,
                'new_stock' => $newQuantity,
                'difference' => $difference
            ];
        });
    }

    /**
     * Get stock transaction history for a product
     *
     * @param string $productCode
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockHistory(string $productCode, int $limit = 50)
    {
        return StockTransaction::where('product_code', $productCode)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}