<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst($report_type) }} Report - MJ's Pharmacy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .summary-card h4 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 14px;
        }
        .summary-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .low-stock {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .low-stock h3 {
            color: #856404;
            margin: 0 0 10px 0;
        }
        .low-stock ul {
            margin: 0;
            padding-left: 20px;
        }
        .low-stock li {
            color: #856404;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MJ's Pharmacy</h1>
        <h2>{{ ucfirst($report_type) }} Report</h2>
        <p><strong>Period:</strong> {{ $start_date }} to {{ $end_date }}</p>
        <p><strong>Generated on:</strong> {{ $generated_at }}</p>
    </div>

    @if($report_type == 'sales')
        <!-- Sales Report Summary -->
        <div class="summary">
            <div class="summary-card">
                <h4>Total Sales</h4>
                <div class="value">₱{{ $data['summary']['total_sales'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Items Sold</h4>
                <div class="value">{{ $data['summary']['total_items'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Transactions</h4>
                <div class="value">{{ $data['summary']['total_transactions'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Average Sale</h4>
                <div class="value">₱{{ $data['summary']['average_sale'] }}</div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        @if(!empty($data['low_stock']) && count($data['low_stock']) > 0)
            <div class="low-stock">
                <h3>⚠️ Low Stock Alerts</h3>
                <ul>
                    @foreach($data['low_stock'] as $item)
                        <li>{{ $item['name'] }} — Only {{ $item['quantity'] }} left</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Sales Data Table -->
        <h3>Sales Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity Sold</th>
                    <th>Unit Price</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['sales'] as $sale)
                    <tr>
                        <td>{{ $sale['product_name'] }}</td>
                        <td>{{ $sale['quantity_sold'] }}</td>
                        <td>₱{{ $sale['unit_price'] }}</td>
                        <td>₱{{ $sale['total_amount'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No sales data found for this period</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($report_type == 'inventory')
        <!-- Inventory Report Summary -->
        <div class="summary">
            <div class="summary-card">
                <h4>Total Products</h4>
                <div class="value">{{ $data['summary']['total_products'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Items</h4>
                <div class="value">{{ $data['summary']['total_items'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Value</h4>
                <div class="value">₱{{ $data['summary']['total_value'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Low Stock Items</h4>
                <div class="value">{{ $data['summary']['low_stock_count'] }}</div>
            </div>
        </div>

        <!-- Inventory Data Table -->
        <h3>Inventory Details</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Generic Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Value</th>
                    <th>Expiry Date</th>
                    <th>Batch Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['inventory'] as $item)
                    <tr>
                        <td>{{ $item['id'] }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['generic_name'] }}</td>
                        <td>{{ $item['category'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>₱{{ $item['unit_price'] }}</td>
                        <td>₱{{ $item['total_value'] }}</td>
                        <td>{{ $item['expiry_date'] }}</td>
                        <td>{{ $item['batch_number'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center;">No inventory data found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($report_type == 'products')
        <!-- Products Report -->
        <h3>Product Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Generic Name</th>
                    <th>Category</th>
                    <th>Stock Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Sold</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['products'] as $product)
                    <tr>
                        <td>{{ $product['id'] }}</td>
                        <td>{{ $product['name'] }}</td>
                        <td>{{ $product['generic_name'] }}</td>
                        <td>{{ $product['category'] }}</td>
                        <td>{{ $product['stock_quantity'] }}</td>
                        <td>₱{{ $product['unit_price'] }}</td>
                        <td>{{ $product['total_sold'] }}</td>
                        <td>₱{{ $product['total_revenue'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center;">No product data found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(!empty($data['categories']))
            <h3>Category Performance</h3>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Count</th>
                        <th>Total Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['categories'] as $category)
                        <tr>
                            <td>{{ $category['category'] }}</td>
                            <td>{{ $category['product_count'] }}</td>
                            <td>{{ $category['total_sold'] }}</td>
                            <td>₱{{ $category['total_revenue'] }}</td>
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