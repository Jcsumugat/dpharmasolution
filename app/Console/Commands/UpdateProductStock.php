<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateProductStock extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:update-stock
                           {--products=* : Specific product IDs to update}
                           {--show-changes : Show detailed changes made}';

    /**
     * The console command description.
     */
    protected $description = 'Update product stock quantities to exclude expired batches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting product stock update...');
        $this->newLine();

        $productIds = $this->option('products');
        $showChanges = $this->option('show-changes');

        if (!empty($productIds)) {
            $products = Product::whereIn('id', $productIds)->get();
            $this->info("Updating stock for " . count($productIds) . " specific products...");
        } else {
            $products = Product::all();
            $this->info("Updating stock for all products...");
        }

        if ($products->isEmpty()) {
            $this->warn('No products found to update.');
            return 0;
        }

        // Check if stock_quantity column exists
        if (!Schema::hasColumn('products', 'stock_quantity')) {
            $this->error('stock_quantity column does not exist in products table.');
            $this->info('Please run the migration first: php artisan migrate');
            return 1;
        }

        $updated = 0;
        $changes = [];

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $oldQuantity = $product->stock_quantity ?? 0;

            // Calculate new quantity from non-expired batches
            $newQuantity = $product->batches()
                ->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now())
                ->sum('quantity_remaining');

            if ($oldQuantity != $newQuantity) {
                $product->update(['stock_quantity' => $newQuantity]);
                $updated++;

                if ($showChanges) {
                    $changes[] = [
                        'id' => $product->id,
                        'name' => $product->product_name,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'difference' => $newQuantity - $oldQuantity
                    ];
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($updated > 0) {
            $this->info("Successfully updated {$updated} products with new stock quantities");

            if ($showChanges && !empty($changes)) {
                $this->newLine();
                $this->info('Changes made:');
                $this->table(
                    ['ID', 'Product Name', 'Old Qty', 'New Qty', 'Difference'],
                    collect($changes)->map(function ($change) {
                        return [
                            $change['id'],
                            substr($change['name'], 0, 30) . (strlen($change['name']) > 30 ? '...' : ''),
                            $change['old_quantity'],
                            $change['new_quantity'],
                            $change['difference'] > 0 ? '+' . $change['difference'] : $change['difference']
                        ];
                    })->toArray()
                );
            }

            // Show summary of expired stock found
            $expiredSummary = DB::select("
                SELECT
                    COUNT(*) as products_with_expired_stock,
                    SUM(expired_quantity) as total_expired_quantity
                FROM (
                    SELECT
                        p.id,
                        COALESCE(expired_batches.expired_quantity, 0) as expired_quantity
                    FROM products p
                    LEFT JOIN (
                        SELECT
                            product_id,
                            SUM(quantity_remaining) as expired_quantity
                        FROM product_batches
                        WHERE quantity_remaining > 0
                        AND expiration_date <= NOW()
                        GROUP BY product_id
                    ) expired_batches ON expired_batches.product_id = p.id
                    WHERE COALESCE(expired_batches.expired_quantity, 0) > 0
                ) summary
            ");

            if (!empty($expiredSummary) && $expiredSummary[0]->products_with_expired_stock > 0) {
                $this->newLine();
                $this->warn("Found {$expiredSummary[0]->products_with_expired_stock} products with expired stock totaling {$expiredSummary[0]->total_expired_quantity} units");
                $this->info("Run 'php artisan batches:cleanup-expired' to see details");
            }

        } else {
            $this->info('No products needed stock quantity updates');
        }

        return 0;
    }
}
