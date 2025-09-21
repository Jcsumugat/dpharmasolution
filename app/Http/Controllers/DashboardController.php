<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $stats = $this->getDashboardStats($filter);
        $activities = $this->getOngoingActivities();
        $salesChartData = $this->getSalesChartData($filter);

        return view('dashboard.dashboard', compact('stats', 'activities', 'salesChartData'));
    }

    public function getStats(Request $request)
    {
        $filter = $request->get('filter', 'all');

        return response()->json([
            'stats' => $this->getDashboardStats($filter),
            'activities' => $this->getOngoingActivities(),
            'salesChart' => $this->getSalesChartData($filter),
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
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
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
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.product_code',
                'products.updated_at as last_updated',
                'products.reorder_level'
            )
            ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as current_stock')
            ->groupBy('products.id', 'products.product_name', 'products.product_code', 'products.updated_at', 'products.reorder_level')
            ->having('current_stock', '=', 0)
            ->orderBy('products.updated_at', 'desc')
            ->get();

        return response()->json($products);
    }

    private function getDashboardStats($filter = 'all')
    {
        $totalSales = 0;
        $totalRevenue = 0;
        $totalCostOfGoodsSold = 0;

        switch ($filter) {
            case 'online':
                $totalSales = DB::table('sales')
                    ->where('status', 'completed')
                    ->sum('total_amount');

                $totalRevenue = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.status', 'completed')
                    ->sum('sale_items.subtotal');

                $totalCostOfGoodsSold = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join(DB::raw('(SELECT product_id, AVG(unit_cost) as avg_unit_cost FROM product_batches GROUP BY product_id) as avg_costs'), 'sale_items.product_id', '=', 'avg_costs.product_id')
                    ->where('sales.status', 'completed')
                    ->sum(DB::raw('sale_items.quantity * avg_costs.avg_unit_cost'));
                break;

            case 'pos':
                $totalSales = DB::table('pos_transactions')
                    ->where('status', 'completed')
                    ->sum('total_amount');

                $totalRevenue = $totalSales;
                $totalCostOfGoodsSold = 0;
                break;

            default:
                $onlineSales = DB::table('sales')
                    ->where('status', 'completed')
                    ->sum('total_amount');

                $posSales = DB::table('pos_transactions')
                    ->where('status', 'completed')
                    ->sum('total_amount');

                $totalSales = $onlineSales + $posSales;

                $onlineRevenue = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.status', 'completed')
                    ->sum('sale_items.subtotal');

                $totalRevenue = $onlineRevenue + $posSales;

                $totalCostOfGoodsSold = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join(DB::raw('(SELECT product_id, AVG(unit_cost) as avg_unit_cost FROM product_batches GROUP BY product_id) as avg_costs'), 'sale_items.product_id', '=', 'avg_costs.product_id')
                    ->where('sales.status', 'completed')
                    ->sum(DB::raw('sale_items.quantity * avg_costs.avg_unit_cost'));
                break;
        }

        $totalProducts = DB::table('products')->count();
        $totalProfit = $totalRevenue - $totalCostOfGoodsSold;

        return [
            'total_sales' => $totalSales ?: 0,
            'total_products' => $totalProducts ?: 0,
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
                return $product->total_stock > 0 && $product->total_stock <= $product->reorder_level;
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

        $posTransactionsToday = DB::table('pos_transactions')
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->count();

        $onlineRevenue = DB::table('sales')
            ->whereDate('sale_date', Carbon::today())
            ->where('status', 'completed')
            ->sum('total_amount');

        $posRevenue = DB::table('pos_transactions')
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('total_amount');

        $todayRevenue = $onlineRevenue + $posRevenue;

        $approvedOrdersToday = DB::table('prescriptions')
            ->whereDate('updated_at', Carbon::today())
            ->where('status', 'approved')
            ->count();

        return [
            'expiring_products' => $expiringProducts ?: 0,
            'pending_prescriptions' => $pendingPrescriptions ?: 0,
            'low_stock_products' => $lowStockProducts ?: 0,
            'out_of_stock_products' => $outOfStockProducts ?: 0,
            'new_orders' => $newOrdersToday ?: 0,
            'completed_orders_today' => $completedOrdersToday ?: 0,
            'pos_transactions_today' => $posTransactionsToday ?: 0,
            'today_revenue' => $todayRevenue ?: 0,
            'approved_orders_today' => $approvedOrdersToday ?: 0,
        ];
    }

    private function getSalesChartData($filter = 'all')
    {
        $salesData = [];
        $labels = [];

        for ($i = 4; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $labels[] = $monthYear;

            $monthlySales = 0;

            switch ($filter) {
                case 'online':
                    $monthlySales = DB::table('sales')
                        ->where('status', 'completed')
                        ->whereYear('sale_date', $date->year)
                        ->whereMonth('sale_date', $date->month)
                        ->sum('total_amount');
                    break;

                case 'pos':
                    $monthlySales = DB::table('pos_transactions')
                        ->where('status', 'completed')
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->sum('total_amount');
                    break;

                default:
                    $onlineMonthlySales = DB::table('sales')
                        ->where('status', 'completed')
                        ->whereYear('sale_date', $date->year)
                        ->whereMonth('sale_date', $date->month)
                        ->sum('total_amount');

                    $posMonthlySales = DB::table('pos_transactions')
                        ->where('status', 'completed')
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->sum('total_amount');

                    $monthlySales = $onlineMonthlySales + $posMonthlySales;
                    break;
            }

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

        $recentPosTransactions = DB::table('pos_transactions')
            ->select(
                'id',
                'transaction_id',
                'customer_name',
                'total_amount',
                'payment_method',
                'created_at'
            )
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'recent_sales' => $recentSales,
            'recent_prescriptions' => $recentPrescriptions,
            'recent_pos_transactions' => $recentPosTransactions
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

        $zeroStockCriticalProducts = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
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

        $onlineWeeklySales = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->sum('total_amount');

        $posWeeklySales = DB::table('pos_transactions')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->sum('total_amount');

        $weeklySales = $onlineWeeklySales + $posWeeklySales;

        $weeklyOnlineOrders = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->count();

        $weeklyPosOrders = DB::table('pos_transactions')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();

        $weeklyOrders = $weeklyOnlineOrders + $weeklyPosOrders;

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
            $lowStockCount = DB::table('products')
                ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
                ->select('products.id', 'products.reorder_level')
                ->selectRaw('COALESCE(SUM(product_batches.quantity_remaining), 0) as total_stock')
                ->groupBy('products.id', 'products.reorder_level')
                ->having('total_stock', '>', 0)
                ->havingRaw('total_stock <= CAST(products.reorder_level AS UNSIGNED)')
                ->whereNotNull('products.reorder_level')
                ->count();

            $outOfStockCount = DB::table('products')
                ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
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

        $outOfStock = DB::table('products')
            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
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
