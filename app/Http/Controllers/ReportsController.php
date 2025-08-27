<?php

namespace App\Http\Controllers;

use App\Exports\ReportsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    /**
     * Display the reports page
     */
    public function index()
    {
        return view('reports.reports');
    }

    /**
     * Generate report based on request parameters
     */
    public function generate(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'report_type' => 'required|in:sales,inventory,products'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $reportType = $request->report_type;

            $data = [];

            switch ($reportType) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($startDate, $endDate);
                    break;
                case 'products':
                    $data = $this->generateProductsReport($startDate, $endDate);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'report_type' => $reportType,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate sales report
     */
    private function generateSalesReport($startDate, $endDate)
    {
        // Get sales data per product
        $salesData = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                'p.product_name',
                DB::raw('SUM(si.quantity) as quantity_sold'),
                DB::raw('AVG(si.unit_price) as unit_price'),
                DB::raw('SUM(si.quantity * si.unit_price) as total_amount')
            )
            ->groupBy('p.id', 'p.product_name')
            ->orderBy('quantity_sold', 'desc')
            ->get();

        // Get summary data
        $summary = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                DB::raw('SUM(si.quantity) as total_items'),
                DB::raw('COUNT(DISTINCT s.id) as total_transactions'),
                DB::raw('AVG(si.quantity * si.unit_price) as average_sale')
            )
            ->first();

        // Get low stock items from product_batches
        $lowStockItems = DB::table('products as p')
            ->join('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->select(
                'p.product_name',
                DB::raw('SUM(pb.quantity_remaining) as total_stock')
            )
            ->groupBy('p.id', 'p.product_name')
            ->having('total_stock', '<=', 20)
            ->orderBy('total_stock', 'asc')
            ->limit(10)
            ->get();

        return [
            'sales' => $salesData->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'quantity_sold' => $item->quantity_sold,
                    'unit_price' => number_format($item->unit_price, 2),
                    'total_amount' => number_format($item->total_amount, 2)
                ];
            }),
            'summary' => [
                'total_sales' => number_format($summary->total_sales ?? 0, 2),
                'total_items' => $summary->total_items ?? 0,
                'total_transactions' => $summary->total_transactions ?? 0,
                'average_sale' => number_format($summary->average_sale ?? 0, 2)
            ],
            'low_stock' => $lowStockItems->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'quantity' => $item->total_stock
                ];
            })
        ];
    }

    /**
     * Generate inventory report
     */
    private function generateInventoryReport($startDate, $endDate)
    {
        // Inventory Details - Now using product_batches
        $inventoryData = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->select(
                'p.id',
                'p.product_name',
                'p.brand_name',
                'c.name as category',
                'pb.batch_number',
                'pb.quantity_remaining',
                'pb.sale_price',
                'pb.expiration_date as expiry_date',
                DB::raw('(pb.quantity_remaining * pb.sale_price) as total_value')
            )
            ->whereNotNull('pb.id') // Only show products with batches
            ->orderBy('pb.quantity_remaining', 'asc')
            ->get();

        // Summary Info - Updated to use product_batches
        $summary = DB::table('products as p')
            ->leftJoin('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->select(
                DB::raw('COUNT(DISTINCT p.id) as total_products'),
                DB::raw('SUM(pb.quantity_remaining) as total_stock'),
                DB::raw('SUM(pb.quantity_remaining * pb.sale_price) as total_value'),
                DB::raw('COUNT(CASE WHEN pb.quantity_remaining <= 20 THEN 1 END) as low_stock_count')
            )
            ->whereNotNull('pb.id')
            ->first();

        // Count expiring products
        $expiringSoon = DB::table('product_batches')
            ->where('expiration_date', '<=', Carbon::now()->addDays(30))
            ->where('quantity_remaining', '>', 0)
            ->count();

        return [
            'inventory' => $inventoryData->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'brand_name' => $item->brand_name,
                    'category' => $item->category ?? 'N/A',
                    'batch_number' => $item->batch_number,
                    'quantity_remaining' => $item->quantity_remaining,
                    'sale_price' => number_format($item->sale_price, 2),
                    'total_value' => number_format($item->total_value, 2),
                    'expiry_date' => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : 'N/A'
                ];
            }),
            'summary' => [
                'total_products' => $summary->total_products ?? 0,
                'total_stock' => $summary->total_stock ?? 0,
                'total_value' => number_format($summary->total_value ?? 0, 2),
                'low_stock_count' => $summary->low_stock_count ?? 0,
                'expiring_soon' => $expiringSoon
            ]
        ];
    }

    /**
     * Generate products report
     */
    private function generateProductsReport($startDate, $endDate)
    {
        // Get product performance data - Updated to use product_batches
        $productData = DB::table('products as p')
            ->leftJoin('sale_items as si', 'p.id', '=', 'si.product_id')
            ->leftJoin('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->select(
                'p.id',
                'p.product_name',
                'p.brand_name',
                'c.name as category',
                DB::raw('SUM(pb.quantity_remaining) as current_stock'),
                DB::raw('AVG(pb.sale_price) as latest_price'),
                DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity ELSE 0 END), 0) as total_sold'),
                DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity * si.unit_price ELSE 0 END), 0) as total_revenue')
            )
            ->addBinding([$startDate, $endDate, $startDate, $endDate], 'select')
            ->groupBy('p.id', 'p.product_name', 'p.brand_name', 'c.name')
            ->orderByDesc('total_sold')
            ->get();

        // Get category performance - Updated to use product_batches for stock info
        $categoryData = DB::table('categories as c')
            ->leftJoin('products as p', 'c.id', '=', 'p.category_id')
            ->leftJoin('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->leftJoin('sale_items as si', 'p.id', '=', 'si.product_id')
            ->leftJoin('sales as s', 'si.sale_id', '=', 's.id')
            ->select(
                'c.name as category',
                DB::raw('COUNT(DISTINCT p.id) as product_count'),
                DB::raw('SUM(pb.quantity_remaining) as total_stock'),
                DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity ELSE 0 END), 0) as total_sold'),
                DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity * si.unit_price ELSE 0 END), 0) as total_revenue')
            )
            ->addBinding([$startDate, $endDate, $startDate, $endDate], 'select')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total_revenue')
            ->get();

        return [
            'products' => $productData->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'brand_name' => $item->brand_name,
                    'category' => $item->category ?? 'N/A',
                    'current_stock' => $item->current_stock ?? 0,
                    'latest_price' => number_format($item->latest_price ?? 0, 2),
                    'total_sold' => $item->total_sold,
                    'total_revenue' => number_format($item->total_revenue, 2)
                ];
            }),
            'categories' => $categoryData->map(function ($item) {
                return [
                    'category' => $item->category ?? 'N/A',
                    'product_count' => $item->product_count,
                    'total_stock' => $item->total_stock ?? 0,
                    'total_sold' => $item->total_sold,
                    'total_revenue' => number_format($item->total_revenue, 2)
                ];
            })
        ];
    }

    /**
     * Export report to PDF
     */
    public function exportPDF(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'report_type' => 'required|in:sales,inventory,products'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $reportType = $request->report_type;

            // Generate report data
            $data = [];
            switch ($reportType) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($startDate, $endDate);
                    break;
                case 'products':
                    $data = $this->generateProductsReport($startDate, $endDate);
                    break;
            }

            // Generate PDF
            $pdf = Pdf::loadView('admin.reports.pdf', [
                'data' => $data,
                'report_type' => $reportType,
                'start_date' => $startDate->format('F j, Y'),
                'end_date' => $endDate->format('F j, Y'),
                'generated_at' => Carbon::now()->format('F j, Y g:i A')
            ]);

            $filename = ucfirst($reportType) . '_Report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'report_type' => 'required|in:sales,inventory,products'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $reportType = $request->report_type;

            // Generate report data
            $data = [];
            switch ($reportType) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($startDate, $endDate);
                    break;
                case 'products':
                    $data = $this->generateProductsReport($startDate, $endDate);
                    break;
            }

            // Generate CSV as alternative
            $filename = ucfirst($reportType) . '_Report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

            $csvData = $this->generateCSV($data, $reportType);

            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate CSV format for export
     */
    private function generateCSV($data, $reportType)
    {
        $output = fopen('php://temp', 'w');

        switch ($reportType) {
            case 'sales':
                // CSV headers
                fputcsv($output, ['Product Name', 'Quantity Sold', 'Unit Price', 'Total Amount']);

                // CSV data
                foreach ($data['sales'] as $sale) {
                    fputcsv($output, [
                        $sale['product_name'],
                        $sale['quantity_sold'],
                        $sale['unit_price'],
                        $sale['total_amount']
                    ]);
                }
                break;

            case 'inventory':
                // CSV headers
                fputcsv($output, ['ID', 'Product Name', 'Brand Name', 'Category', 'Batch Number', 'Stock Remaining', 'Sale Price', 'Total Value', 'Expiry Date']);

                // CSV data
                foreach ($data['inventory'] as $item) {
                    fputcsv($output, [
                        $item['id'],
                        $item['product_name'],
                        $item['brand_name'],
                        $item['category'],
                        $item['batch_number'],
                        $item['quantity_remaining'],
                        $item['sale_price'],
                        $item['total_value'],
                        $item['expiry_date']
                    ]);
                }
                break;

            case 'products':
                // CSV headers
                fputcsv($output, ['ID', 'Product Name', 'Brand Name', 'Category', 'Current Stock', 'Latest Price', 'Total Sold', 'Total Revenue']);

                // CSV data
                foreach ($data['products'] as $product) {
                    fputcsv($output, [
                        $product['id'],
                        $product['product_name'],
                        $product['brand_name'],
                        $product['category'],
                        $product['current_stock'],
                        $product['latest_price'],
                        $product['total_sold'],
                        $product['total_revenue']
                    ]);
                }
                break;
        }

        rewind($output);
        $csvData = stream_get_contents($output);
        fclose($output);

        return $csvData;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            $today = Carbon::today();

            // Today's sales
            $todaySales = DB::table('sales as s')
                ->join('sale_items as si', 's.id', '=', 'si.sale_id')
                ->whereDate('s.created_at', $today)
                ->select(
                    DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT s.id) as total_transactions')
                )
                ->first();

            // This month's sales
            $monthSales = DB::table('sales as s')
                ->join('sale_items as si', 's.id', '=', 'si.sale_id')
                ->whereMonth('s.created_at', Carbon::now()->month)
                ->whereYear('s.created_at', Carbon::now()->year)
                ->select(
                    DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT s.id) as total_transactions')
                )
                ->first();

            // Low stock count - Updated to use product_batches
            $lowStockCount = DB::table('products as p')
                ->join('product_batches as pb', 'p.id', '=', 'pb.product_id')
                ->select('p.id', DB::raw('SUM(pb.quantity_remaining) as total_stock'))
                ->groupBy('p.id')
                ->having('total_stock', '<=', 20)
                ->count();

            // Expiring products count - Updated to use product_batches
            $expiringCount = DB::table('product_batches')
                ->where('expiration_date', '<=', Carbon::now()->addDays(30))
                ->where('quantity_remaining', '>', 0)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'today_sales' => number_format($todaySales->total_sales ?? 0, 2),
                    'today_transactions' => $todaySales->total_transactions ?? 0,
                    'month_sales' => number_format($monthSales->total_sales ?? 0, 2),
                    'month_transactions' => $monthSales->total_transactions ?? 0,
                    'low_stock_count' => $lowStockCount,
                    'expiring_count' => $expiringCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales chart data
     */
    public function getSalesChart(Request $request)
    {
        try {
            $days = $request->input('days', 7); // Default to 7 days
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();

            $salesData = DB::table('sales as s')
                ->join('sale_items as si', 's.id', '=', 'si.sale_id')
                ->whereBetween('s.created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(s.created_at) as date'),
                    DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT s.id) as total_transactions')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $salesData->map(function ($item) {
                    return [
                        'date' => Carbon::parse($item->date)->format('M j'),
                        'sales' => floatval($item->total_sales),
                        'transactions' => intval($item->total_transactions)
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sales chart data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top selling products
     */
    public function getTopProducts(Request $request)
    {
        try {
            $days = $request->input('days', 30); // Default to 30 days
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();

            $topProducts = DB::table('products as p')
                ->join('sale_items as si', 'p.id', '=', 'si.product_id')
                ->join('sales as s', 'si.sale_id', '=', 's.id')
                ->whereBetween('s.created_at', [$startDate, $endDate])
                ->select(
                    'p.product_name',
                    DB::raw('SUM(si.quantity) as total_sold'),
                    DB::raw('SUM(si.quantity * si.unit_price) as total_revenue')
                )
                ->groupBy('p.id', 'p.product_name')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $topProducts->map(function ($item) {
                    return [
                        'name' => $item->product_name,
                        'total_sold' => intval($item->total_sold),
                        'total_revenue' => floatval($item->total_revenue)
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching top products: ' . $e->getMessage()
            ], 500);
        }
    }
}