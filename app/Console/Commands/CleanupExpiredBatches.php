<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupExpiredBatches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'batches:cleanup-expired
                           {--auto : Automatically mark expired batches as expired}
                           {--days=30 : Number of days to look ahead for expiring batches}
                           {--update-stock : Update product stock quantities}';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup expired batches and update stock quantities to exclude expired inventory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired batch cleanup...');
        $this->newLine();

        $autoMarkExpired = $this->option('auto');
        $days = (int) $this->option('days');
        $updateStock = $this->option('update-stock');

        // 1. Get expired batches report
        $this->info('Generating expiration report...');
        $expirationReport = $this->generateExpirationReport($days);

        $this->displayExpirationReport($expirationReport);

        // 2. Cleanup expired batches if requested
        if ($autoMarkExpired) {
            $this->newLine();
            $this->info('Cleaning up expired batches...');

            if ($this->confirm('This will mark all expired batches as expired in stock movements. Continue?')) {
                $cleanupResults = $this->cleanupExpiredBatches(true);
                $this->displayCleanupResults($cleanupResults);
            } else {
                $this->warn('Cleanup cancelled by user.');
            }
        }

        // 3. Update product stock quantities if requested
        if ($updateStock) {
            $this->newLine();
            $this->info('Updating product stock quantities...');
            $this->call('products:update-stock', ['--show-changes' => true]);
        }

        // 4. Display current inventory stats
        $this->newLine();
        $this->info('Current inventory statistics:');
        $stats = $this->getInventoryStats();
        $this->displayInventoryStats($stats);

        // 5. Show recommendations
        $this->newLine();
        $this->showRecommendations($expirationReport, $stats);

        $this->newLine();
        $this->info('Cleanup process completed!');

        return 0;
    }

    /**
     * Generate expiration report
     */
    private function generateExpirationReport($days = 90)
    {
        $now = now();

        // Expired batches
        $expiredBatches = ProductBatch::with('product')
            ->where('expiration_date', '<=', $now)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiration_date')
            ->get();

        // Critical (expiring in 1-7 days)
        $criticalBatches = ProductBatch::with('product')
            ->where('expiration_date', '>', $now)
            ->where('expiration_date', '<=', $now->copy()->addDays(7))
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiration_date')
            ->get();

        // Warning (expiring in 8-30 days)
        $warningBatches = ProductBatch::with('product')
            ->where('expiration_date', '>', $now->copy()->addDays(7))
            ->where('expiration_date', '<=', $now->copy()->addDays(30))
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiration_date')
            ->get();

        return [
            'report_date' => $now->format('Y-m-d H:i:s'),
            'period_days' => $days,
            'expired' => [
                'batches' => $expiredBatches,
                'total_quantity' => $expiredBatches->sum('quantity_remaining'),
                'total_value' => $expiredBatches->sum(function ($batch) {
                    return $batch->quantity_remaining * $batch->unit_cost;
                })
            ],
            'critical' => [
                'batches' => $criticalBatches,
                'total_quantity' => $criticalBatches->sum('quantity_remaining'),
            ],
            'warning' => [
                'batches' => $warningBatches,
                'total_quantity' => $warningBatches->sum('quantity_remaining'),
            ]
        ];
    }

    /**
     * Display expiration report in console
     */
    private function displayExpirationReport($report)
    {
        $this->table(
            ['Category', 'Batches', 'Quantity', 'Value'],
            [
                [
                    'Expired',
                    $report['expired']['batches']->count(),
                    number_format($report['expired']['total_quantity']),
                    'P' . number_format($report['expired']['total_value'], 2)
                ],
                [
                    'Critical (1-7 days)',
                    $report['critical']['batches']->count(),
                    number_format($report['critical']['total_quantity']),
                    '-'
                ],
                [
                    'Warning (8-30 days)',
                    $report['warning']['batches']->count(),
                    number_format($report['warning']['total_quantity']),
                    '-'
                ]
            ]
        );

        if ($report['expired']['batches']->isNotEmpty()) {
            $this->newLine();
            $this->warn('Expired batches found:');

            $expiredData = $report['expired']['batches']->take(10)->map(function ($batch) {
                $daysExpired = abs(now()->diffInDays($batch->expiration_date, false));
                return [
                    substr($batch->product->product_name, 0, 25) . (strlen($batch->product->product_name) > 25 ? '...' : ''),
                    $batch->batch_number,
                    $batch->expiration_date->format('M d, Y'),
                    $batch->quantity_remaining,
                    $daysExpired . ' days ago'
                ];
            })->toArray();

            $this->table(
                ['Product', 'Batch', 'Expired Date', 'Quantity', 'Days Ago'],
                $expiredData
            );

            if ($report['expired']['batches']->count() > 10) {
                $this->info('... and ' . ($report['expired']['batches']->count() - 10) . ' more expired batches');
            }
        }

        if ($report['critical']['batches']->isNotEmpty()) {
            $this->newLine();
            $this->error('Critical - Expiring within 7 days:');

            $criticalData = $report['critical']['batches']->take(5)->map(function ($batch) {
                $daysLeft = now()->diffInDays($batch->expiration_date, false);
                return [
                    substr($batch->product->product_name, 0, 25) . (strlen($batch->product->product_name) > 25 ? '...' : ''),
                    $batch->batch_number,
                    $batch->expiration_date->format('M d, Y'),
                    $batch->quantity_remaining,
                    $daysLeft . ' days'
                ];
            })->toArray();

            $this->table(
                ['Product', 'Batch', 'Expires', 'Quantity', 'Days Left'],
                $criticalData
            );

            if ($report['critical']['batches']->count() > 5) {
                $this->info('... and ' . ($report['critical']['batches']->count() - 5) . ' more critical batches');
            }
        }
    }

    /**
     * Clean up expired batches
     */
    private function cleanupExpiredBatches($autoMarkExpired = false)
    {
        $expiredBatches = ProductBatch::with('product')
            ->where('expiration_date', '<=', now())
            ->where('quantity_remaining', '>', 0)
            ->get();

        $cleanupResults = [];

        foreach ($expiredBatches as $batch) {
            $result = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_name' => $batch->product->product_name,
                'expired_quantity' => $batch->quantity_remaining,
                'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                'days_expired' => abs(now()->diffInDays($batch->expiration_date, false))
            ];

            if ($autoMarkExpired) {
                try {
                    // Create stock movement for expired stock
                    StockMovement::create([
                        'product_id' => $batch->product_id,
                        'type' => 'expired',
                        'quantity' => -$batch->quantity_remaining,
                        'reference_id' => null,
                        'reference_type' => 'expiry',
                        'notes' => "Auto-cleanup of expired batch {$batch->batch_number}"
                    ]);

                    $result['action'] = 'marked_expired';
                    $result['success'] = true;
                } catch (\Exception $e) {
                    $result['action'] = 'failed';
                    $result['success'] = false;
                    $result['error'] = $e->getMessage();
                }
            } else {
                $result['action'] = 'identified_only';
                $result['success'] = true;
            }

            $cleanupResults[] = $result;
        }

        return [
            'total_expired_batches' => count($cleanupResults),
            'total_expired_quantity' => collect($cleanupResults)->sum('expired_quantity'),
            'cleanup_performed' => $autoMarkExpired,
            'results' => $cleanupResults
        ];
    }

    /**
     * Display cleanup results
     */
    private function displayCleanupResults($results)
    {
        $this->info("Processed {$results['total_expired_batches']} expired batches");
        $this->info("Total expired quantity: {$results['total_expired_quantity']} units");

        $successful = collect($results['results'])->where('success', true)->count();
        $failed = collect($results['results'])->where('success', false)->count();

        if ($successful > 0) {
            $this->info("Successfully processed: {$successful} batches");
        }
        if ($failed > 0) {
            $this->error("Failed to process: {$failed} batches");
        }
    }

    /**
     * Get inventory statistics
     */
    private function getInventoryStats()
    {
        return [
            'total_available_products' => Product::whereHas('batches', function ($q) {
                $q->where('quantity_remaining', '>', 0)
                  ->where('expiration_date', '>', now());
            })->count(),

            'total_available_quantity' => ProductBatch::where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now())
                ->sum('quantity_remaining'),

            'total_available_value' => ProductBatch::where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now())
                ->selectRaw('SUM(unit_cost * quantity_remaining)')
                ->value('SUM(unit_cost * quantity_remaining)') ?: 0,

            'expired_products_count' => Product::whereHas('batches', function ($q) {
                $q->where('quantity_remaining', '>', 0)
                  ->where('expiration_date', '<=', now());
            })->count(),

            'total_expired_quantity' => ProductBatch::where('expiration_date', '<=', now())
                ->sum('quantity_remaining'),

            'total_expired_value' => ProductBatch::where('expiration_date', '<=', now())
                ->selectRaw('SUM(unit_cost * quantity_remaining)')
                ->value('SUM(unit_cost * quantity_remaining)') ?: 0,

            'expiring_soon_count' => ProductBatch::where('expiration_date', '>', now())
                ->where('expiration_date', '<=', now()->addDays(30))
                ->where('quantity_remaining', '>', 0)
                ->count(),

            'expiring_soon_quantity' => ProductBatch::where('expiration_date', '>', now())
                ->where('expiration_date', '<=', now()->addDays(30))
                ->where('quantity_remaining', '>', 0)
                ->sum('quantity_remaining'),

            'out_of_stock_products' => Product::whereDoesntHave('batches', function ($q) {
                $q->where('quantity_remaining', '>', 0)
                  ->where('expiration_date', '>', now());
            })->count(),
        ];
    }

    /**
     * Display inventory statistics
     */
    private function displayInventoryStats($stats)
    {
        $this->table(
            ['Metric', 'Count', 'Quantity', 'Value'],
            [
                [
                    'Available Products',
                    $stats['total_available_products'],
                    number_format($stats['total_available_quantity']),
                    'P' . number_format($stats['total_available_value'], 2)
                ],
                [
                    'Expired Products',
                    $stats['expired_products_count'],
                    number_format($stats['total_expired_quantity']),
                    'P' . number_format($stats['total_expired_value'], 2)
                ],
                [
                    'Expiring Soon (30 days)',
                    $stats['expiring_soon_count'],
                    number_format($stats['expiring_soon_quantity']),
                    '-'
                ],
                [
                    'Out of Stock',
                    $stats['out_of_stock_products'],
                    '-',
                    '-'
                ]
            ]
        );
    }

    /**
     * Show recommendations based on current state
     */
    private function showRecommendations($report, $stats)
    {
        $this->info('Recommendations:');

        $recommendations = [];

        // Expired stock recommendations
        if ($report['expired']['total_quantity'] > 0) {
            $recommendations[] = "• Remove {$report['expired']['total_quantity']} units of expired stock (P" .
                number_format($report['expired']['total_value'], 2) . " value)";
        }

        // Critical expiring stock
        if ($report['critical']['total_quantity'] > 0) {
            $recommendations[] = "• Urgent: {$report['critical']['total_quantity']} units expiring within 7 days - consider discounting";
        }

        // Warning expiring stock
        if ($report['warning']['total_quantity'] > 0) {
            $recommendations[] = "• {$report['warning']['total_quantity']} units expiring in 8-30 days - plan promotions";
        }

        // Out of stock warnings
        if ($stats['out_of_stock_products'] > 0) {
            $recommendations[] = "• {$stats['out_of_stock_products']} products have no available (non-expired) stock";
        }

        // Performance recommendations
        if ($stats['expired_products_count'] > 0) {
            $recommendations[] = "• Run automated cleanup: php artisan batches:cleanup-expired --auto";
        }

        if ($report['warning']['batches']->count() > 0) {
            $recommendations[] = "• Schedule promotions for {$report['warning']['batches']->count()} batches expiring in 8-30 days";
        }

        if (empty($recommendations)) {
            $this->info('• All inventory is in good condition!');
        } else {
            foreach ($recommendations as $recommendation) {
                $this->line($recommendation);
            }
        }

        $this->newLine();
        $this->info('Available commands:');
        $this->line('• php artisan batches:cleanup-expired --auto (mark expired batches)');
        $this->line('• php artisan batches:cleanup-expired --update-stock (update product quantities)');
        $this->line('• php artisan products:update-stock (update stock quantities only)');
    }
}
