<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ ucfirst($report_type) }} Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 11px;
            color: #666;
            margin: 3px 0;
        }

        .summary-grid {
            display: table;
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        .summary-row {
            display: table-row;
        }

        .summary-card {
            display: table-cell;
            padding: 15px;
            border: 1px solid #ddd;
            text-align: center;
            width: 25%;
        }

        .summary-card h4 {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .breakdown-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .breakdown-section h3 {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .breakdown-grid {
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .breakdown-card {
            display: table-cell;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            width: 50%;
        }

        .breakdown-card h4 {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .breakdown-amount {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th,
        table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        h3 {
            font-size: 16px;
            margin: 25px 0 15px 0;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 5px;
        }

        .low-stock-alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }

        .low-stock-alert h3 {
            color: #856404;
            border-bottom: none;
            margin-bottom: 10px;
        }

        .low-stock-alert ul {
            margin-left: 20px;
        }

        .low-stock-alert li {
            margin-bottom: 5px;
            color: #856404;
        }

        .source-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .source-badge.online {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .source-badge.walkin {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #81c784;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MJ's Pharmacy</h1>
        <h2>{{ ucfirst($report_type) }} Report</h2>
        <p><strong>Date Range:</strong> {{ $start_date }} – {{ $end_date }}</p>
        @if($report_type === 'sales')
            <p><strong>Sales Source:</strong> {{ $sales_source === 'all' ? 'All Sales (Online + Walk-in)' : ($sales_source === 'online' ? 'Online Orders Only' : 'Walk-in Sales Only') }}</p>
        @endif
        <p><strong>Generated On:</strong> {{ $generated_at }}</p>
    </div>

    @if($report_type === 'sales')
        <!-- Sales Report -->
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-card">
                    <h4>Total Sales</h4>
                    <div class="value">PHP {{ $data['summary']['total_sales'] ?? '0.00' }}</div>
                </div>
                <div class="summary-card">
                    <h4>Total Items Sold</h4>
                    <div class="value">{{ $data['summary']['total_items'] ?? '0' }}</div>
                </div>
                <div class="summary-card">
                    <h4>Total Transactions</h4>
                    <div class="value">{{ $data['summary']['total_transactions'] ?? '0' }}</div>
                </div>
                <div class="summary-card">
                    <h4>Average Sale</h4>
                    <div class="value">PHP {{ $data['summary']['average_sale'] ?? '0.00' }}</div>
                </div>
            </div>
        </div>

        @if(isset($data['breakdown']) && $sales_source === 'all')
        <div class="breakdown-section">
            <h3>Sales Breakdown by Source</h3>
            <div class="breakdown-grid">
                <div class="breakdown-card">
                    <h4>Online Orders</h4>
                    <div class="breakdown-amount">PHP {{ $data['breakdown']['online_sales'] ?? '0.00' }}</div>
                    <small>{{ $data['breakdown']['online_transactions'] ?? 0 }} transactions</small>
                </div>
                <div class="breakdown-card">
                    <h4>Walk-in Sales</h4>
                    <div class="breakdown-amount">PHP {{ $data['breakdown']['walkin_sales'] ?? '0.00' }}</div>
                    <small>{{ $data['breakdown']['walkin_transactions'] ?? 0 }} transactions</small>
                </div>
            </div>
        </div>
        @endif

        @if(isset($data['low_stock']) && count($data['low_stock']) > 0)
        <div class="low-stock-alert">
            <h3>Low Stock Alerts</h3>
            <ul>
                @foreach($data['low_stock'] as $item)
                    <li>{{ $item['name'] }} — Only {{ $item['quantity'] }} left</li>
                @endforeach
            </ul>
        </div>
        @endif

        <h3>Detailed Sales Report</h3>
        @if(isset($data['sales']) && count($data['sales']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Quantity Sold</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total Amount</th>
                    @if($sales_source === 'all')
                    <th class="text-center">Source</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($data['sales'] as $sale)
                <tr>
                    <td>{{ $sale['product_name'] }}</td>
                    <td class="text-center">{{ $sale['quantity_sold'] }}</td>
                    <td class="text-right">PHP {{ $sale['unit_price'] }}</td>
                    <td class="text-right">PHP {{ $sale['total_amount'] }}</td>
                    @if($sales_source === 'all')
                    <td class="text-center">
                        <span class="source-badge {{ $sale['source'] }}">
                            {{ $sale['source'] === 'online' ? 'Online' : 'Walk-in' }}
                        </span>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No sales data found for the selected period.</p>
        @endif

    @elseif($report_type === 'inventory')
        <!-- Inventory Report -->
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-card">
                    <h4>Total Products</h4>
                    <div class="value">{{ $data['summary']['total_products'] ?? '0' }}</div>
                </div>
                <div class="summary-card">
                    <h4>Total Stock</h4>
                    <div class="value">{{ $data['summary']['total_stock'] ?? '0' }}</div>
                </div>
                <div class="summary-card">
                    <h4>Total Value</h4>
                    <div class="value">PHP {{ $data['summary']['total_value'] ?? '0.00' }}</div>
                </div>
                <div class="summary-card">
                    <h4>Low Stock Items</h4>
                    <div class="value">{{ $data['summary']['low_stock_count'] ?? '0' }}</div>
                </div>
            </div>
        </div>

        <h3>Inventory Details</h3>
        @if(isset($data['inventory']) && count($data['inventory']) > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Batch #</th>
                    <th class="text-right">Stock</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total Value</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['inventory'] as $item)
                <tr>
                    <td>{{ $item['id'] }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['brand_name'] }}</td>
                    <td>{{ $item['category'] }}</td>
                    <td>{{ $item['batch_number'] }}</td>
                    <td class="text-right">{{ $item['quantity_remaining'] }}</td>
                    <td class="text-right">PHP {{ $item['sale_price'] }}</td>
                    <td class="text-right">PHP {{ $item['total_value'] }}</td>
                    <td>{{ $item['expiry_date'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No inventory data found.</p>
        @endif

    @else
        <!-- Products Report -->
        <h3>Product Performance</h3>
        @if(isset($data['products']) && count($data['products']) > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th class="text-right">Current Stock</th>
                    <th class="text-right">Latest Price</th>
                    <th class="text-right">Total Sold</th>
                    <th class="text-right">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['products'] as $product)
                <tr>
                    <td>{{ $product['id'] }}</td>
                    <td>{{ $product['product_name'] }}</td>
                    <td>{{ $product['brand_name'] }}</td>
                    <td>{{ $product['category'] }}</td>
                    <td class="text-right">{{ $product['current_stock'] }}</td>
                    <td class="text-right">PHP {{ $product['latest_price'] }}</td>
                    <td class="text-right">{{ $product['total_sold'] }}</td>
                    <td class="text-right">PHP {{ $product['total_revenue'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No product data found.</p>
        @endif

        @if(isset($data['categories']) && count($data['categories']) > 0)
        <h3>Category Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-center">Product Count</th>
                    <th class="text-right">Total Stock</th>
                    <th class="text-right">Total Sold</th>
                    <th class="text-right">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['categories'] as $category)
                <tr>
                    <td>{{ $category['category'] }}</td>
                    <td class="text-center">{{ $category['product_count'] }}</td>
                    <td class="text-right">{{ $category['total_stock'] }}</td>
                    <td class="text-right">{{ $category['total_sold'] }}</td>
                    <td class="text-right">PHP {{ $category['total_revenue'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    @endif

    <div class="footer">
        <p>This report was generated automatically by MJ's Pharmacy Management System</p>
        <p>For questions or concerns, please contact the system administrator</p>
    </div>
</body>
</html>
