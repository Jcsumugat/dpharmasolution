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
                'report_type' => 'required|in:sales,inventory,products',
                'sales_source' => 'in:all,online,walkin'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $reportType = $request->report_type;
            $salesSource = $request->input('sales_source', 'all');

            $data = [];

            switch ($reportType) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate, $salesSource);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($startDate, $endDate);
                    break;
                case 'products':
                    $data = $this->generateProductsReport($startDate, $endDate, $salesSource);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'report_type' => $reportType,
                'sales_source' => $salesSource,
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
     * Generate sales report with POS integration
     */
    private function generateSalesReport($startDate, $endDate, $salesSource = 'all')
    {
        // Build combined sales data query
        $salesQuery = $this->buildCombinedSalesQuery($startDate, $endDate, $salesSource);
        $salesData = $salesQuery->get();

        // Get summary data
        $summary = $this->getSalesSummary($startDate, $endDate, $salesSource);

        // Get breakdown by source if showing all sales
        $breakdown = null;
        if ($salesSource === 'all') {
            $breakdown = $this->getSalesBreakdown($startDate, $endDate);
        }

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
                    'total_amount' => number_format($item->total_amount, 2),
                    'source' => $item->source
                ];
            }),
            'summary' => [
                'total_sales' => number_format($summary->total_sales ?? 0, 2),
                'total_items' => $summary->total_items ?? 0,
                'total_transactions' => $summary->total_transactions ?? 0,
                'average_sale' => number_format($summary->average_sale ?? 0, 2)
            ],
            'breakdown' => $breakdown,
            'low_stock' => $lowStockItems->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'quantity' => $item->total_stock
                ];
            })
        ];
    }

    /**
     * Build combined sales query for online and walk-in sales
     */
    private function buildCombinedSalesQuery($startDate, $endDate, $salesSource)
    {
        $onlineQuery = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                'p.product_name',
                DB::raw('SUM(si.quantity) as quantity_sold'),
                DB::raw('AVG(si.unit_price) as unit_price'),
                DB::raw('SUM(si.quantity * si.unit_price) as total_amount'),
                DB::raw("'online' as source")
            )
            ->groupBy('p.id', 'p.product_name');

        $walkinQuery = DB::table('pos_transactions as pt')
            ->join('pos_transaction_items as pti', 'pt.id', '=', 'pti.transaction_id')
            ->whereBetween('pt.created_at', [$startDate, $endDate])
            ->where('pt.status', '!=', 'cancelled')
            ->select(
                'pti.product_name',
                DB::raw('SUM(pti.quantity) as quantity_sold'),
                DB::raw('AVG(pti.unit_price) as unit_price'),
                DB::raw('SUM(pti.total_price) as total_amount'),
                DB::raw("'walkin' as source")
            )
            ->groupBy('pti.product_name');

        // Apply sales source filter
        if ($salesSource === 'online') {
            return $onlineQuery->orderBy('quantity_sold', 'desc');
        } elseif ($salesSource === 'walkin') {
            return $walkinQuery->orderBy('quantity_sold', 'desc');
        } else {
            // Combine both queries using UNION
            return DB::query()
                ->fromSub($onlineQuery->union($walkinQuery), 'combined_sales')
                ->select(
                    'product_name',
                    DB::raw('SUM(quantity_sold) as quantity_sold'),
                    DB::raw('AVG(unit_price) as unit_price'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw("CASE
                        WHEN COUNT(DISTINCT source) > 1 THEN 'both'
                        ELSE MIN(source)
                    END as source")
                )
                ->groupBy('product_name')
                ->orderBy('quantity_sold', 'desc');
        }
    }

    /**
     * Get sales summary
     */
    private function getSalesSummary($startDate, $endDate, $salesSource)
    {
        $onlineSummary = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                DB::raw('SUM(si.quantity) as total_items'),
                DB::raw('COUNT(DISTINCT s.id) as total_transactions')
            )
            ->first();

        $walkinSummary = DB::table('pos_transactions as pt')
            ->join('pos_transaction_items as pti', 'pt.id', '=', 'pti.transaction_id')
            ->whereBetween('pt.created_at', [$startDate, $endDate])
            ->where('pt.status', '!=', 'cancelled')
            ->select(
                DB::raw('SUM(pti.total_price) as total_sales'),
                DB::raw('SUM(pti.quantity) as total_items'),
                DB::raw('COUNT(DISTINCT pt.id) as total_transactions')
            )
            ->first();

        $totalSales = 0;
        $totalItems = 0;
        $totalTransactions = 0;

        if ($salesSource === 'online') {
            $totalSales = $onlineSummary->total_sales ?? 0;
            $totalItems = $onlineSummary->total_items ?? 0;
            $totalTransactions = $onlineSummary->total_transactions ?? 0;
        } elseif ($salesSource === 'walkin') {
            $totalSales = $walkinSummary->total_sales ?? 0;
            $totalItems = $walkinSummary->total_items ?? 0;
            $totalTransactions = $walkinSummary->total_transactions ?? 0;
        } else {
            $totalSales = ($onlineSummary->total_sales ?? 0) + ($walkinSummary->total_sales ?? 0);
            $totalItems = ($onlineSummary->total_items ?? 0) + ($walkinSummary->total_items ?? 0);
            $totalTransactions = ($onlineSummary->total_transactions ?? 0) + ($walkinSummary->total_transactions ?? 0);
        }

        $averageSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        return (object) [
            'total_sales' => $totalSales,
            'total_items' => $totalItems,
            'total_transactions' => $totalTransactions,
            'average_sale' => $averageSale
        ];
    }

    /**
     * Get sales breakdown by source
     */
    private function getSalesBreakdown($startDate, $endDate)
    {
        $onlineStats = DB::table('sales as s')
            ->join('sale_items as si', 's.id', '=', 'si.sale_id')
            ->whereBetween('s.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                DB::raw('COUNT(DISTINCT s.id) as total_transactions')
            )
            ->first();

        $walkinStats = DB::table('pos_transactions as pt')
            ->join('pos_transaction_items as pti', 'pt.id', '=', 'pti.transaction_id')
            ->whereBetween('pt.created_at', [$startDate, $endDate])
            ->where('pt.status', '!=', 'cancelled')
            ->select(
                DB::raw('SUM(pti.total_price) as total_sales'),
                DB::raw('COUNT(DISTINCT pt.id) as total_transactions')
            )
            ->first();

        return [
            'online_sales' => number_format($onlineStats->total_sales ?? 0, 2),
            'online_transactions' => $onlineStats->total_transactions ?? 0,
            'walkin_sales' => number_format($walkinStats->total_sales ?? 0, 2),
            'walkin_transactions' => $walkinStats->total_transactions ?? 0
        ];
    }

    /**
     * Generate inventory report
     */
    private function generateInventoryReport($startDate, $endDate)
    {
        // Inventory Details - Using product_batches
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
            ->whereNotNull('pb.id')
            ->orderBy('pb.quantity_remaining', 'asc')
            ->get();

        // Summary Info
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
     * Generate products report with POS integration
     */
    private function generateProductsReport($startDate, $endDate, $salesSource = 'all')
    {
        // Get product performance data combining online and walk-in sales
        $productData = $this->getProductPerformanceData($startDate, $endDate, $salesSource);

        // Get category performance
        $categoryData = $this->getCategoryPerformanceData($startDate, $endDate, $salesSource);

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
     * Get product performance data combining online and POS sales
     */
    private function getProductPerformanceData($startDate, $endDate, $salesSource)
    {
        $baseQuery = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->select(
                'p.id',
                'p.product_name',
                'p.brand_name',
                'c.name as category',
                DB::raw('SUM(pb.quantity_remaining) as current_stock'),
                DB::raw('AVG(pb.sale_price) as latest_price')
            )
            ->groupBy('p.id', 'p.product_name', 'p.brand_name', 'c.name');

        if ($salesSource === 'online') {
            return $baseQuery
                ->leftJoin('sale_items as si', 'p.id', '=', 'si.product_id')
                ->leftJoin('sales as s', 'si.sale_id', '=', 's.id')
                ->addSelect(
                    DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity ELSE 0 END), 0) as total_sold'),
                    DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity * si.unit_price ELSE 0 END), 0) as total_revenue')
                )
                ->addBinding([$startDate, $endDate, $startDate, $endDate], 'select')
                ->orderByDesc('total_sold')
                ->get();
        } elseif ($salesSource === 'walkin') {
            return $baseQuery
                ->leftJoin('pos_transaction_items as pti', 'p.product_name', '=', 'pti.product_name')
                ->leftJoin('pos_transactions as pt', 'pti.transaction_id', '=', 'pt.id')
                ->addSelect(
                    DB::raw('COALESCE(SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.quantity ELSE 0 END), 0) as total_sold'),
                    DB::raw('COALESCE(SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.total_price ELSE 0 END), 0) as total_revenue')
                )
                ->addBinding([$startDate, $endDate, $startDate, $endDate], 'select')
                ->orderByDesc('total_sold')
                ->get();
        } else {
            // Combined query for all sales sources
            return $baseQuery
                ->leftJoin('sale_items as si', 'p.id', '=', 'si.product_id')
                ->leftJoin('sales as s', 'si.sale_id', '=', 's.id')
                ->leftJoin('pos_transaction_items as pti', 'p.product_name', '=', 'pti.product_name')
                ->leftJoin('pos_transactions as pt', 'pti.transaction_id', '=', 'pt.id')
                ->addSelect(
                    DB::raw('COALESCE(
                        SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity ELSE 0 END) +
                        SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.quantity ELSE 0 END), 0
                    ) as total_sold'),
                    DB::raw('COALESCE(
                        SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity * si.unit_price ELSE 0 END) +
                        SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.total_price ELSE 0 END), 0
                    ) as total_revenue')
                )
                ->addBinding([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate], 'select')
                ->orderByDesc('total_sold')
                ->get();
        }
    }

    /**
     * Get category performance data
     */
    private function getCategoryPerformanceData($startDate, $endDate, $salesSource)
    {
        $baseQuery = DB::table('categories as c')
            ->leftJoin('products as p', 'c.id', '=', 'p.category_id')
            ->leftJoin('product_batches as pb', 'p.id', '=', 'pb.product_id')
            ->select(
                'c.name as category',
                DB::raw('COUNT(DISTINCT p.id) as product_count'),
                DB::raw('SUM(pb.quantity_remaining) as total_stock')
            )
            ->groupBy('c.id', 'c.name');

        if ($salesSource === 'online') {
            return $baseQuery
                ->leftJoin('sale_items as si', 'p.id', '=', 'si.product_id')
                ->leftJoin('sales as s', 'si.sale_id', '=', 's.id')
                ->addSelect(
                    DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity ELSE 0 END), 0) as total_sold'),
                    DB::raw('COALESCE(SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity * si.unit_price ELSE 0 END), 0) as total_revenue')
                )
                ->addBinding([$startDate, $endDate, $startDate, $endDate], 'select')
                ->orderByDesc('total_revenue')
                ->get();
        } elseif ($salesSource === 'walkin') {
            return $baseQuery
                ->leftJoin('pos_transaction_items as pti', 'p.product_name', '=', 'pti.product_name')
                ->leftJoin('pos_transactions as pt', 'pti.transaction_id', '=', 'pt.id')
                ->addSelect(
                    DB::raw('COALESCE(SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.quantity ELSE 0 END), 0) as total_sold'),
                    DB::raw('COALESCE(SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.total_price ELSE 0 END), 0) as total_revenue')
                )
                ->addBinding([$startDate, $endDate, $startDate, $endDate], 'select')
                ->orderByDesc('total_revenue')
                ->get();
        } else {
            return $baseQuery
                ->leftJoin('sale_items as si', 'p.id', '=', 'si.product_id')
                ->leftJoin('sales as s', 'si.sale_id', '=', 's.id')
                ->leftJoin('pos_transaction_items as pti', 'p.product_name', '=', 'pti.product_name')
                ->leftJoin('pos_transactions as pt', 'pti.transaction_id', '=', 'pt.id')
                ->addSelect(
                    DB::raw('COALESCE(
                        SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity ELSE 0 END) +
                        SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.quantity ELSE 0 END), 0
                    ) as total_sold'),
                    DB::raw('COALESCE(
                        SUM(CASE WHEN s.created_at BETWEEN ? AND ? THEN si.quantity * si.unit_price ELSE 0 END) +
                        SUM(CASE WHEN pt.created_at BETWEEN ? AND ? AND pt.status != "cancelled" THEN pti.total_price ELSE 0 END), 0
                    ) as total_revenue')
                )
                ->addBinding([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate], 'select')
                ->orderByDesc('total_revenue')
                ->get();
        }
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
                'report_type' => 'required|in:sales,inventory,products',
                'sales_source' => 'in:all,online,walkin'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $reportType = $request->report_type;
            $salesSource = $request->input('sales_source', 'all');

            // Generate report data
            $data = [];
            switch ($reportType) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate, $salesSource);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($startDate, $endDate);
                    break;
                case 'products':
                    $data = $this->generateProductsReport($startDate, $endDate, $salesSource);
                    break;
            }

            // Generate PDF
            $pdf = Pdf::loadView('reports.pdf', [
                'data' => $data,
                'report_type' => $reportType,
                'sales_source' => $salesSource,
                'start_date' => $startDate->format('F j, Y'),
                'end_date' => $endDate->format('F j, Y'),
                'generated_at' => Carbon::now()->format('F j, Y g:i A')
            ]);

            $sourceText = $salesSource === 'all' ? 'All_Sales' : ucfirst($salesSource);
            $filename = ucfirst($reportType) . '_Report_' . $sourceText . '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';

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
                'report_type' => 'required|in:sales,inventory,products',
                'sales_source' => 'in:all,online,walkin'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $reportType = $request->report_type;
            $salesSource = $request->input('sales_source', 'all');

            // Generate report data
            $data = [];
            switch ($reportType) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate, $salesSource);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($startDate, $endDate);
                    break;
                case 'products':
                    $data = $this->generateProductsReport($startDate, $endDate, $salesSource);
                    break;
            }

            // Generate CSV
            $sourceText = $salesSource === 'all' ? 'All_Sales' : ucfirst($salesSource);
            $filename = ucfirst($reportType) . '_Report_' . $sourceText . '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

            $csvData = $this->generateCSV($data, $reportType, $salesSource);

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
    private function generateCSV($data, $reportType, $salesSource = 'all')
    {
        $output = fopen('php://temp', 'w');

        switch ($reportType) {
            case 'sales':
                // CSV headers
                $headers = ['Product Name', 'Quantity Sold', 'Unit Price', 'Total Amount'];
                if ($salesSource === 'all') {
                    $headers[] = 'Source';
                }
                fputcsv($output, $headers);

                // CSV data
                foreach ($data['sales'] as $sale) {
                    $row = [
                        $sale['product_name'],
                        $sale['quantity_sold'],
                        $sale['unit_price'],
                        $sale['total_amount']
                    ];
                    if ($salesSource === 'all') {
                        $row[] = ucfirst($sale['source']);
                    }
                    fputcsv($output, $row);
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
     * Get dashboard statistics with POS integration
     */
    public function getDashboardStats()
    {
        try {
            $today = Carbon::today();

            // Today's online sales
            $todayOnlineSales = DB::table('sales as s')
                ->join('sale_items as si', 's.id', '=', 'si.sale_id')
                ->whereDate('s.created_at', $today)
                ->select(
                    DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT s.id) as total_transactions')
                )
                ->first();

            // Today's walk-in sales
            $todayWalkinSales = DB::table('pos_transactions as pt')
                ->join('pos_transaction_items as pti', 'pt.id', '=', 'pti.transaction_id')
                ->whereDate('pt.created_at', $today)
                ->where('pt.status', '!=', 'cancelled')
                ->select(
                    DB::raw('SUM(pti.total_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT pt.id) as total_transactions')
                )
                ->first();

            // This month's online sales
            $monthOnlineSales = DB::table('sales as s')
                ->join('sale_items as si', 's.id', '=', 'si.sale_id')
                ->whereMonth('s.created_at', Carbon::now()->month)
                ->whereYear('s.created_at', Carbon::now()->year)
                ->select(
                    DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT s.id) as total_transactions')
                )
                ->first();

            // This month's walk-in sales
            $monthWalkinSales = DB::table('pos_transactions as pt')
                ->join('pos_transaction_items as pti', 'pt.id', '=', 'pti.transaction_id')
                ->whereMonth('pt.created_at', Carbon::now()->month)
                ->whereYear('pt.created_at', Carbon::now()->year)
                ->where('pt.status', '!=', 'cancelled')
                ->select(
                    DB::raw('SUM(pti.total_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT pt.id) as total_transactions')
                )
                ->first();

            // Combine totals
            $todayTotal = ($todayOnlineSales->total_sales ?? 0) + ($todayWalkinSales->total_sales ?? 0);
            $todayTransactions = ($todayOnlineSales->total_transactions ?? 0) + ($todayWalkinSales->total_transactions ?? 0);
            $monthTotal = ($monthOnlineSales->total_sales ?? 0) + ($monthWalkinSales->total_sales ?? 0);
            $monthTransactions = ($monthOnlineSales->total_transactions ?? 0) + ($monthWalkinSales->total_transactions ?? 0);

            // Low stock count
            $lowStockCount = DB::table('products as p')
                ->join('product_batches as pb', 'p.id', '=', 'pb.product_id')
                ->select('p.id', DB::raw('SUM(pb.quantity_remaining) as total_stock'))
                ->groupBy('p.id')
                ->having('total_stock', '<=', 20)
                ->count();

            // Expiring products count
            $expiringCount = DB::table('product_batches')
                ->where('expiration_date', '<=', Carbon::now()->addDays(30))
                ->where('quantity_remaining', '>', 0)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'today_sales' => number_format($todayTotal, 2),
                    'today_transactions' => $todayTransactions,
                    'month_sales' => number_format($monthTotal, 2),
                    'month_transactions' => $monthTransactions,
                    'low_stock_count' => $lowStockCount,
                    'expiring_count' => $expiringCount,
                    'breakdown' => [
                        'today_online' => number_format($todayOnlineSales->total_sales ?? 0, 2),
                        'today_walkin' => number_format($todayWalkinSales->total_sales ?? 0, 2),
                        'month_online' => number_format($monthOnlineSales->total_sales ?? 0, 2),
                        'month_walkin' => number_format($monthWalkinSales->total_sales ?? 0, 2)
                    ]
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
     * Get sales chart data with POS integration
     */
    public function getSalesChart(Request $request)
    {
        try {
            $days = $request->input('days', 7);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();

            // Online sales data
            $onlineSalesData = DB::table('sales as s')
                ->join('sale_items as si', 's.id', '=', 'si.sale_id')
                ->whereBetween('s.created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(s.created_at) as date'),
                    DB::raw('SUM(si.quantity * si.unit_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT s.id) as total_transactions')
                )
                ->groupBy('date')
                ->get();

            // Walk-in sales data
            $walkinSalesData = DB::table('pos_transactions as pt')
                ->join('pos_transaction_items as pti', 'pt.id', '=', 'pti.transaction_id')
                ->whereBetween('pt.created_at', [$startDate, $endDate])
                ->where('pt.status', '!=', 'cancelled')
                ->select(
                    DB::raw('DATE(pt.created_at) as date'),
                    DB::raw('SUM(pti.total_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT pt.id) as total_transactions')
                )
                ->groupBy('date')
                ->get();

            // Combine and format data
            $combinedData = [];
            $dateRange = [];

            // Generate date range
            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateRange[] = $date->format('Y-m-d');
            }

            foreach ($dateRange as $date) {
                $onlineData = $onlineSalesData->firstWhere('date', $date);
                $walkinData = $walkinSalesData->firstWhere('date', $date);

                $totalSales = ($onlineData->total_sales ?? 0) + ($walkinData->total_sales ?? 0);
                $totalTransactions = ($onlineData->total_transactions ?? 0) + ($walkinData->total_transactions ?? 0);

                $combinedData[] = [
                    'date' => Carbon::parse($date)->format('M j'),
                    'sales' => floatval($totalSales),
                    'transactions' => intval($totalTransactions),
                    'online_sales' => floatval($onlineData->total_sales ?? 0),
                    'walkin_sales' => floatval($walkinData->total_sales ?? 0)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $combinedData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sales chart data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top selling products with POS integration
     */
    public function getTopProducts(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();

            // Online sales
            $onlineProducts = DB::table('products as p')
                ->join('sale_items as si', 'p.id', '=', 'si.product_id')
                ->join('sales as s', 'si.sale_id', '=', 's.id')
                ->whereBetween('s.created_at', [$startDate, $endDate])
                ->select(
                    'p.product_name',
                    DB::raw('SUM(si.quantity) as total_sold'),
                    DB::raw('SUM(si.quantity * si.unit_price) as total_revenue')
                )
                ->groupBy('p.id', 'p.product_name')
                ->get();

            // Walk-in sales
            $walkinProducts = DB::table('pos_transaction_items as pti')
                ->join('pos_transactions as pt', 'pti.transaction_id', '=', 'pt.id')
                ->whereBetween('pt.created_at', [$startDate, $endDate])
                ->where('pt.status', '!=', 'cancelled')
                ->select(
                    'pti.product_name',
                    DB::raw('SUM(pti.quantity) as total_sold'),
                    DB::raw('SUM(pti.total_price) as total_revenue')
                )
                ->groupBy('pti.product_name')
                ->get();

            // Combine and aggregate
            $combined = [];

            foreach ($onlineProducts as $product) {
                $combined[$product->product_name] = [
                    'name' => $product->product_name,
                    'total_sold' => $product->total_sold,
                    'total_revenue' => $product->total_revenue
                ];
            }

            foreach ($walkinProducts as $product) {
                if (isset($combined[$product->product_name])) {
                    $combined[$product->product_name]['total_sold'] += $product->total_sold;
                    $combined[$product->product_name]['total_revenue'] += $product->total_revenue;
                } else {
                    $combined[$product->product_name] = [
                        'name' => $product->product_name,
                        'total_sold' => $product->total_sold,
                        'total_revenue' => $product->total_revenue
                    ];
                }
            }

            // Sort by total sold and take top 10
            $topProducts = collect($combined)
                ->sortByDesc('total_sold')
                ->take(10)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $topProducts->map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'total_sold' => intval($item['total_sold']),
                        'total_revenue' => floatval($item['total_revenue'])
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
