<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add stock_quantity column to products table if it doesn't exist
        if (!Schema::hasColumn('products', 'stock_quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('stock_quantity')->default(0)->after('reorder_level');
            });
        }

        // Add indexes for better performance when filtering expired batches
        Schema::table('product_batches', function (Blueprint $table) {
            // Composite index for availability queries (non-expired + in-stock)
            if (!$this->indexExists('product_batches', 'idx_batches_availability')) {
                $table->index(['expiration_date', 'quantity_remaining'], 'idx_batches_availability');
            }

            // Composite index for FIFO queries (product + expiration + received date)
            if (!$this->indexExists('product_batches', 'idx_batches_fifo')) {
                $table->index(['product_id', 'expiration_date', 'received_date'], 'idx_batches_fifo');
            }

            // Index for expired batch queries
            if (!$this->indexExists('product_batches', 'idx_batches_expired')) {
                $table->index(['expiration_date', 'quantity_remaining'], 'idx_batches_expired');
            }
        });

        // Update existing products' stock_quantity to reflect only non-expired batches
        $this->updateProductStockQuantities();

        // Add expiration notification tracking column if desired
        if (!Schema::hasColumn('product_batches', 'expiration_notification_sent_at')) {
            Schema::table('product_batches', function (Blueprint $table) {
                $table->timestamp('expiration_notification_sent_at')->nullable()->after('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            if ($this->indexExists('product_batches', 'idx_batches_availability')) {
                $table->dropIndex('idx_batches_availability');
            }
            if ($this->indexExists('product_batches', 'idx_batches_fifo')) {
                $table->dropIndex('idx_batches_fifo');
            }
            if ($this->indexExists('product_batches', 'idx_batches_expired')) {
                $table->dropIndex('idx_batches_expired');
            }

            if (Schema::hasColumn('product_batches', 'expiration_notification_sent_at')) {
                $table->dropColumn('expiration_notification_sent_at');
            }
        });

        // Optionally remove the stock_quantity column
        // Uncomment if you want to revert completely
        /*
        if (Schema::hasColumn('products', 'stock_quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stock_quantity');
            });
        }
        */
    }

    /**
     * Update all products' stock_quantity to reflect only non-expired batches
     */
    private function updateProductStockQuantities(): void
    {
        echo "Updating product stock quantities to exclude expired batches...\n";

        // Update all products' stock quantities
        $sql = "
            UPDATE products
            SET stock_quantity = (
                SELECT COALESCE(SUM(quantity_remaining), 0)
                FROM product_batches
                WHERE product_batches.product_id = products.id
                AND quantity_remaining > 0
                AND expiration_date > NOW()
            )
        ";

        DB::statement($sql);

        // Get information about what was updated for logging
        $affectedProducts = DB::select("
            SELECT
                p.id,
                p.product_name,
                p.stock_quantity as new_available_quantity,
                COALESCE(expired_batches.expired_quantity, 0) as expired_quantity,
                COALESCE(expired_batches.expired_quantity + p.stock_quantity, p.stock_quantity) as total_quantity
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
            ORDER BY expired_batches.expired_quantity DESC
        ");

        if (!empty($affectedProducts)) {
            \Log::info('Updated product stock quantities to exclude expired batches', [
                'affected_products' => count($affectedProducts),
                'total_expired_units' => collect($affectedProducts)->sum('expired_quantity'),
                'affected_products_details' => $affectedProducts
            ]);

            echo "Updated " . count($affectedProducts) . " products with expired stock\n";

            // Show first few products as examples
            $examples = array_slice($affectedProducts, 0, 5);
            foreach ($examples as $product) {
                echo "- {$product->product_name}: {$product->new_available_quantity} available, {$product->expired_quantity} expired\n";
            }

            if (count($affectedProducts) > 5) {
                echo "... and " . (count($affectedProducts) - 5) . " more products\n";
            }
        } else {
            echo "No products with expired stock found\n";
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
