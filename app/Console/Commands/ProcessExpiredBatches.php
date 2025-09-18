<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessExpiredBatches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:process-expired-batches
                            {--dry-run : Show what would be processed without making changes}
                            {--force : Process batches even if already processed today}';

    /**
     * The console command description.
     */
    protected $description = 'Process expired product batches and update stock quantities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Starting expired batch processing...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Find all expired batches with remaining quantity > 0
            $expiredBatches = ProductBatch::expired()
                ->inStock()
                ->with(['product', 'supplier'])
                ->get();

            if ($expiredBatches->isEmpty()) {
                $this->info('No expired batches found with remaining stock.');
                return;
            }

            $this->info("Found {$expiredBatches->count()} expired batches to process:");

            // Group by product for better reporting
            $batchesByProduct = $expiredBatches->groupBy('product_id');

            $totalLossValue = 0;
            $totalQuantityExpired = 0;
            $processedProducts = 0;

            DB::beginTransaction();

            foreach ($batchesByProduct as $productId => $productBatches) {
                $product = $productBatches->first()->product;
                $productLossValue = 0;
                $productQuantityExpired = 0;

                $this->line("\nProcessing {$product->product_name} ({$product->product_code}):");

                foreach ($productBatches as $batch) {
                    $lossValue = $batch->quantity_remaining * $batch->unit_cost;
                    $productLossValue += $lossValue;
                    $productQuantityExpired += $batch->quantity_remaining;
                    $totalLossValue += $lossValue;
                    $totalQuantityExpired += $batch->quantity_remaining;

                    $expirationDays = now()->diffInDays($batch->expiration_date);

                    $this->line("  • Batch {$batch->batch_number}: {$batch->quantity_remaining} units, expired {$expirationDays} days ago, loss: ₱" . number_format($lossValue, 2));

                    if (!$isDryRun) {
                        // Create stock movement for expired batch
                        StockMovement::createExpiredMovement(
                            $batch->product_id,
                            $batch->quantity_remaining,
                            "Auto-expired batch: {$batch->batch_number} (expired {$expirationDays} days ago, loss: ₱" . number_format($lossValue, 2) . ")",
                            $batch->id
                        );

                        // Set batch quantity to 0
                        $batch->update(['quantity_remaining' => 0]);

                        Log::info('Batch auto-expired', [
                            'batch_id' => $batch->id,
                            'batch_number' => $batch->batch_number,
                            'product_id' => $batch->product_id,
                            'product_name' => $product->product_name,
                            'expired_quantity' => $batch->quantity_remaining,
                            'loss_value' => $lossValue,
                            'expiration_date' => $batch->expiration_date,
                            'days_expired' => $expirationDays
                        ]);
                    }
                }

                if (!$isDryRun) {
                    // Update product's cached stock quantity
                    $product->updateCachedFields();
                    $processedProducts++;
                }

                $this->info("  Total for {$product->product_name}: {$productQuantityExpired} units, ₱" . number_format($productLossValue, 2) . " loss");
            }

            if (!$isDryRun) {
                DB::commit();

                $this->info("\n✅ Processing completed successfully!");
                $this->info("Products updated: {$processedProducts}");
                $this->info("Total expired quantity: " . number_format($totalQuantityExpired));
                $this->info("Total loss value: ₱" . number_format($totalLossValue, 2));

                // Log summary
                Log::info('Expired batch processing completed', [
                    'products_updated' => $processedProducts,
                    'total_expired_quantity' => $totalQuantityExpired,
                    'total_loss_value' => $totalLossValue,
                    'batches_processed' => $expiredBatches->count()
                ]);
            } else {
                DB::rollback();
                $this->info("\n📋 DRY RUN SUMMARY:");
                $this->info("Would process: {$processedProducts} products");
                $this->info("Would expire: " . number_format($totalQuantityExpired) . " units");
                $this->info("Estimated loss: ₱" . number_format($totalLossValue, 2));
                $this->info("\nRun without --dry-run to execute changes.");
            }

        } catch (\Exception $e) {
            DB::rollback();

            $this->error("Error processing expired batches: " . $e->getMessage());

            Log::error('Expired batch processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }

        return 0;
    }

    /**
     * Check if processing has already been done today
     */
    private function hasProcessedToday()
    {
        // You can implement a check here if you want to prevent multiple runs per day
        // For example, check a log table or cache
        return false;
    }

    /**
     * Get summary of products that will be affected
     */
    private function getAffectedProductsSummary()
    {
        return Product::whereHas('expiredBatches')
            ->with(['expiredBatches'])
            ->get()
            ->map(function ($product) {
                $expiredBatches = $product->expiredBatches;
                return [
                    'product_name' => $product->product_name,
                    'product_code' => $product->product_code,
                    'expired_batches_count' => $expiredBatches->count(),
                    'expired_quantity' => $expiredBatches->sum('quantity_remaining'),
                    'loss_value' => $expiredBatches->sum(function ($batch) {
                        return $batch->quantity_remaining * $batch->unit_cost;
                    })
                ];
            });
    }
}
