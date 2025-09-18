<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getDashboardStats();
        $activities = $this->getOngoingActivities();
        $salesChartData = $this->getSalesChartData();

        return view('dashboard.dashboard', compact('stats', 'activities', 'salesChartData'));
    }

    public function getStats()
    {
        return response()->json([
            'stats' => $this->getDashboardStats(),
            'activities' => $this->getOngoingActivities(),
            'salesChart' => $this->getSalesChartData(),
            'timestamp' => now()->toISOString()
        ]);
    }

    public function getExpiringProducts()
    {
        $products = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
            ->where('product_batches.expiration_date', '<=', Carbon::now()->addDays(30))
            ->where('product_batches.expiration_date', '>=', Carbon::now())
            ->where('product_batches.quantity_remaining', '>', 0)
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.product_code',
                'product_batches.batch_number',
                'product_batches.expiration_date',
                'product_batches.quantity_remaining'
            )
            ->orderBy('product_batches.expiration_date', 'asc')
            ->get();

        return response()->json($products);
    }

    public function getLowStockProducts()
    {
        $products = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id') // Use INNER JOIN instead of LEFT JOIN
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.product_code',
                'products.reorder_level'
            )
            ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as current_stock')
            ->groupBy('products.id', 'products.product_name', 'products.product_code', 'products.reorder_level')
            ->having('current_stock', '>', 0)
            ->havingRaw('current_stock <= CAST(products.reorder_level AS UNSIGNED)')
            ->whereNotNull('products.reorder_level')
            ->orderBy('current_stock', 'asc')
            ->get();

        return response()->json($products);
    }

    public function getOutOfStockProducts()
    {
        $products = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id') // Use INNER JOIN - only products with batches
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.product_code',
                'products.updated_at as last_updated',
                'products.reorder_level'
            )
            ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as current_stock')
            ->groupBy('products.id', 'products.product_name', 'products.product_code', 'products.updated_at', 'products.reorder_level')
            ->having('current_stock', '=', 0) // Products that had stock but now have 0
            ->orderBy('products.updated_at', 'desc')
            ->get();

        return response()->json($products);
    }
    private function getDashboardStats()
    {
        $totalSales = DB::table('sales')
            ->where('status', 'completed')
            ->sum('total_amount');

        $totalProducts = DB::table('products')->count();

        $capitalSpend = DB::table('product_batches')
            ->sum(DB::raw('quantity_received * unit_cost'));

        $totalRevenue = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->sum('sale_items.subtotal');

        $totalCostOfGoodsSold = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join(DB::raw('(SELECT product_id, AVG(unit_cost) as avg_unit_cost FROM product_batches GROUP BY product_id) as avg_costs'), 'sale_items.product_id', '=', 'avg_costs.product_id')
            ->where('sales.status', 'completed')
            ->sum(DB::raw('sale_items.quantity * avg_costs.avg_unit_cost'));

        $totalProfit = $totalRevenue - $totalCostOfGoodsSold;

        return [
            'total_sales' => $totalSales ?: 0,
            'total_products' => $totalProducts ?: 0,
            'capital_spend' => $capitalSpend ?: 0,
            'total_profit' => $totalProfit ?: 0,
        ];
    }

private function getOngoingActivities()
{
    $expiringProducts = DB::table('products')
        ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
        ->where('product_batches.expiration_date', '<=', Carbon::now()->addDays(30))
        ->where('product_batches.expiration_date', '>=', Carbon::now())
        ->where('product_batches.quantity_remaining', '>', 0)
        ->distinct('products.id')
        ->count('products.id');

    $pendingPrescriptions = DB::table('prescriptions')
        ->where('status', 'pending')
        ->count();

    // Fixed low stock query - now checks for stock BELOW or EQUAL to reorder level
    $lowStockProducts = DB::table('products')
        ->leftJoin('product_batches', 'products.id', '=', 'product_batches.product_id')
        ->select('products.id', 'products.product_name', 'products.reorder_level')
        ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
        ->where(function($query) {
            $query->whereNotNull('products.reorder_level')
                  ->where('products.reorder_level', '>', 0);
        })
        ->groupBy('products.id', 'products.product_name', 'products.reorder_level')
        ->get()
        ->filter(function($product) {
            // Log for debugging
            \Log::info("Checking: {$product->product_name}, Stock: {$product->total_stock}, Reorder: {$product->reorder_level}");

            // Alert when stock is AT OR BELOW reorder level (but not zero)
            $isLowStock = $product->total_stock > 0 && $product->total_stock <= $product->reorder_level;

            if ($isLowStock) {
                \Log::info("LOW STOCK ALERT: {$product->product_name}");
            }

            return $isLowStock;
        })
        ->count();

    $outOfStockProducts = DB::table('products')
        ->leftJoin('product_batches', 'products.id', '=', 'product_batches.product_id')
        ->select('products.id')
        ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
        ->groupBy('products.id')
        ->having('total_stock', '=', 0)
        ->whereExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('product_batches as pb2')
                  ->whereRaw('pb2.product_id = products.id');
        })
        ->count();

    $newOrdersToday = DB::table('prescriptions')
        ->whereDate('created_at', Carbon::today())
        ->count();

    $completedOrdersToday = DB::table('sales')
        ->whereDate('sale_date', Carbon::today())
        ->where('status', 'completed')
        ->count();

    $todayRevenue = DB::table('sales')
        ->whereDate('sale_date', Carbon::today())
        ->where('status', 'completed')
        ->sum('total_amount');

    $approvedOrdersToday = DB::table('prescriptions')
        ->whereDate('updated_at', Carbon::today())
        ->where('status', 'approved')
        ->count();

    \Log::info("Final low stock count: {$lowStockProducts}");

    return [
        'expiring_products' => $expiringProducts ?: 0,
        'pending_prescriptions' => $pendingPrescriptions ?: 0,
        'low_stock_products' => $lowStockProducts ?: 0,
        'out_of_stock_products' => $outOfStockProducts ?: 0,
        'new_orders' => $newOrdersToday ?: 0,
        'completed_orders_today' => $completedOrdersToday ?: 0,
        'today_revenue' => $todayRevenue ?: 0,
        'approved_orders_today' => $approvedOrdersToday ?: 0,
    ];
}

// Method to manually set up test data for low stock alerts
public function setupLowStockTest()
{
    // Option 1: Reduce stock quantity for one product
    DB::table('product_batches')
        ->where('product_id', 2) // Amoxicillin
        ->limit(1)
        ->update(['quantity_remaining' => 30]); // Below reorder level of 50

    // Option 2: Increase reorder level to trigger alert
    DB::table('products')
        ->where('id', 1) // Paracetamol
        ->update(['reorder_level' => 400]); // Above current stock of 395

    return "Test data set up. Paracetamol should now show low stock alert.";
}

// Method to reset test data
public function resetTestData()
{
    // Reset quantities
    DB::table('product_batches')
        ->where('product_id', 2)
        ->update(['quantity_remaining' => 84]);

    // Reset reorder levels
    DB::table('products')
        ->whereIn('id', [1, 2, 3])
        ->update(['reorder_level' => 50]);

    return "Test data reset to original values.";
}

    private function getSalesChartData()
    {
        $salesData = [];
        $labels = [];

        for ($i = 4; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $labels[] = $monthYear;

            $monthlySales = DB::table('sales')
                ->where('status', 'completed')
                ->whereYear('sale_date', $date->year)
                ->whereMonth('sale_date', $date->month)
                ->sum('total_amount');

            $salesData[] = $monthlySales ?: 0;
        }

        return [
            'labels' => $labels,
            'data' => $salesData,
        ];
    }

    public function getRecentActivity()
    {
        $recentSales = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'sales.id',
                'sales.total_amount',
                'sales.sale_date',
                'sales.payment_method',
                'customers.full_name as customer_name'
            )
            ->where('sales.status', 'completed')
            ->orderBy('sales.sale_date', 'desc')
            ->limit(5)
            ->get();

        $recentPrescriptions = DB::table('prescriptions')
            ->leftJoin('customers', 'prescriptions.customer_id', '=', 'customers.id')
            ->select(
                'prescriptions.id',
                'prescriptions.status',
                'prescriptions.created_at',
                'prescriptions.mobile_number',
                'customers.full_name as customer_name'
            )
            ->orderBy('prescriptions.created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'recent_sales' => $recentSales,
            'recent_prescriptions' => $recentPrescriptions
        ]);
    }

    public function getTopProducts()
    {
        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'products.product_name',
                'products.product_code',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.product_name', 'products.product_code')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        return response()->json(['top_products' => $topProducts]);
    }

    public function getCriticalAlerts()
    {
        $criticalAlerts = [];

        $expiredProducts = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
            ->where('product_batches.expiration_date', '<', Carbon::now())
            ->where('product_batches.quantity_remaining', '>', 0)
            ->select(
                'products.product_name',
                'products.product_code',
                'product_batches.batch_number',
                'product_batches.expiration_date',
                'product_batches.quantity_remaining'
            )
            ->get();

        if ($expiredProducts->count() > 0) {
            $criticalAlerts[] = [
                'type' => 'expired_products',
                'level' => 'critical',
                'message' => $expiredProducts->count() . ' expired product batches still in inventory',
                'data' => $expiredProducts
            ];
        }

        // Fixed: Only check products that have had batches
        $zeroStockCriticalProducts = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id') // INNER JOIN
            ->select('products.product_name', 'products.product_code')
            ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
            ->where('products.product_type', 'essential')
            ->groupBy('products.id', 'products.product_name', 'products.product_code')
            ->having('total_stock', '=', 0)
            ->get();

        if ($zeroStockCriticalProducts->count() > 0) {
            $criticalAlerts[] = [
                'type' => 'critical_stock_out',
                'level' => 'critical',
                'message' => $zeroStockCriticalProducts->count() . ' essential products out of stock',
                'data' => $zeroStockCriticalProducts
            ];
        }

        $highValuePendingOrders = DB::table('prescriptions')
            ->where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->count();

        if ($highValuePendingOrders > 0) {
            $criticalAlerts[] = [
                'type' => 'pending_orders',
                'level' => 'warning',
                'message' => $highValuePendingOrders . ' orders pending for over 24 hours',
                'data' => []
            ];
        }

        return response()->json(['critical_alerts' => $criticalAlerts]);
    }

    public function getWeeklySummary()
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $weeklySales = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->sum('total_amount');

        $weeklyOrders = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->count();

        $weeklyPrescriptions = DB::table('prescriptions')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();

        $averageDailyRevenue = $weeklyOrders > 0 ? $weeklySales / 7 : 0;

        return response()->json([
            'weekly_summary' => [
                'total_sales' => $weeklySales,
                'total_orders' => $weeklyOrders,
                'total_prescriptions' => $weeklyPrescriptions,
                'average_daily_revenue' => $averageDailyRevenue,
                'period' => [
                    'start' => $weekStart->format('M d'),
                    'end' => $weekEnd->format('M d, Y')
                ]
            ]
        ]);
    }

    public function checkInventoryAlerts()
    {
        try {
            // Fixed: Only count products that have had batches
            $lowStockCount = DB::table('products')
                ->join('product_batches', 'products.id', '=', 'product_batches.product_id') // INNER JOIN
                ->select('products.id', 'products.reorder_level')
                ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
                ->groupBy('products.id', 'products.reorder_level')
                ->having('total_stock', '>', 0)
                ->havingRaw('total_stock <= CAST(products.reorder_level AS UNSIGNED)')
                ->whereNotNull('products.reorder_level')
                ->count();

            // Fixed: Only count products that have had batches but are now out of stock
            $outOfStockCount = DB::table('products')
                ->join('product_batches', 'products.id', '=', 'product_batches.product_id') // INNER JOIN
                ->select('products.id')
                ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
                ->groupBy('products.id')
                ->having('total_stock', '=', 0)
                ->count();

            $expiringCount = DB::table('products')
                ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
                ->where('product_batches.expiration_date', '<=', Carbon::now()->addDays(30))
                ->where('product_batches.expiration_date', '>', Carbon::now())
                ->where('product_batches.quantity_remaining', '>', 0)
                ->distinct('products.id')
                ->count('products.id');

            $totalCount = $lowStockCount + $outOfStockCount + $expiringCount;

            $criticalExpiringCount = DB::table('products')
                ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
                ->where('product_batches.expiration_date', '<=', Carbon::now()->addDays(7))
                ->where('product_batches.expiration_date', '>', Carbon::now())
                ->where('product_batches.quantity_remaining', '>', 0)
                ->distinct('products.id')
                ->count('products.id');

            $criticalCount = $outOfStockCount + $criticalExpiringCount;

            return response()->json([
                'has_alerts' => $totalCount > 0,
                'total_count' => $totalCount,
                'critical_count' => $criticalCount,
                'counts' => [
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                    'expiring' => $expiringCount
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Inventory alerts check failed: ' . $e->getMessage());
            return response()->json([
                'has_alerts' => false,
                'total_count' => 0,
                'critical_count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getProductStockDetails($productId)
    {
        $stockDetails = DB::table('product_batches')
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->get();

        $totalStock = $stockDetails->sum('quantity_remaining');

        return response()->json([
            'total_stock' => $totalStock,
            'batches' => $stockDetails
        ]);
    }

    public function getUrgentItems()
    {
        $urgentExpiring = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
            ->where('product_batches.expiration_date', '<=', Carbon::now()->addDays(7))
            ->where('product_batches.expiration_date', '>', Carbon::now())
            ->where('product_batches.quantity_remaining', '>', 0)
            ->select(
                'products.product_name',
                'products.product_code',
                'product_batches.batch_number',
                'product_batches.expiration_date',
                'product_batches.quantity_remaining'
            )
            ->orderBy('product_batches.expiration_date', 'asc')
            ->get();

        // Fixed: Only show products that have had batches but are now out of stock
        $outOfStock = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id') // INNER JOIN
            ->select(
                'products.product_name',
                'products.product_code',
                'products.reorder_level'
            )
            ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
            ->groupBy('products.id', 'products.product_name', 'products.product_code', 'products.reorder_level')
            ->having('total_stock', '=', 0)
            ->get();

        return response()->json([
            'urgent_expiring' => $urgentExpiring,
            'out_of_stock' => $outOfStock
        ]);
    }

    private function getAlternateProfitCalculation()
    {
        $totalRevenue = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->sum('sale_items.subtotal');

        $weightedCosts = DB::table('product_batches')
            ->select('product_id')
            ->selectRaw('SUM(quantity_received * unit_cost) / SUM(quantity_received) as weighted_avg_cost')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $totalCOGS = 0;
        $salesWithCosts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->select('sale_items.product_id', 'sale_items.quantity')
            ->get();

        foreach ($salesWithCosts as $sale) {
            if (isset($weightedCosts[$sale->product_id])) {
                $totalCOGS += $sale->quantity * $weightedCosts[$sale->product_id]->weighted_avg_cost;
            }
        }

        return [
            'revenue' => $totalRevenue,
            'cogs' => $totalCOGS,
            'profit' => $totalRevenue - $totalCOGS
        ];
    }

    public function getInventoryMetrics()
    {
        $avgInventoryValue = DB::table('product_batches')
            ->selectRaw('AVG(quantity_remaining * unit_cost) as avg_value')
            ->value('avg_value');

        $annualCOGS = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join(DB::raw('(SELECT product_id, AVG(unit_cost) as avg_unit_cost FROM product_batches GROUP BY product_id) as avg_costs'), 'sale_items.product_id', '=', 'avg_costs.product_id')
            ->where('sales.status', 'completed')
            ->where('sales.sale_date', '>=', Carbon::now()->subYear())
            ->sum(DB::raw('sale_items.quantity * avg_costs.avg_unit_cost'));

        $inventoryTurnover = $avgInventoryValue > 0 ? $annualCOGS / $avgInventoryValue : 0;

        return response()->json([
            'avg_inventory_value' => $avgInventoryValue ?: 0,
            'annual_cogs' => $annualCOGS ?: 0,
            'inventory_turnover' => round($inventoryTurnover, 2)
        ]);
    }
}
