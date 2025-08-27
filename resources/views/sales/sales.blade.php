<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/sales.css') }}">
</head>

<body>
    @include('admin.admin-header')

    <div class="content">
        <div class="sales-header">
            <h2>💰 Sales Dashboard</h2>
            <div class="sales-filters">
                <select id="dateFilter" class="filter-select">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
        </div>

        <!-- Sales Summary Cards -->
        <div class="sales-summary">
            <div class="summary-card">
                <h3>Total Sales</h3>
                <p class="summary-value">₱{{ number_format($salesStats['total_sales'], 2) }}</p>
                <small>{{ $salesStats['completed_count'] }} completed transactions</small>
            </div>
            <div class="summary-card">
                <h3>Today's Sales</h3>
                <p class="summary-value">₱{{ number_format($salesStats['today_sales'], 2) }}</p>
                <small>{{ $salesStats['today_count'] }} transactions today</small>
            </div>
            <div class="summary-card">
                <h3>Pending Orders</h3>
                <p class="summary-value">{{ $salesStats['pending_count'] }}</p>
                <small>₱{{ number_format($salesStats['pending_value'], 2) }} pending value</small>
            </div>
            <div class="summary-card">
                <h3>Average Order</h3>
                <p class="summary-value">₱{{ number_format($salesStats['average_order'], 2) }}</p>
                <small>Per completed transaction</small>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="sales-table-container">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Customer</th>
                        <th>Prescription</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                    @php
                        // Ensure sale_date is a Carbon instance
                        $saleDate = $sale->sale_date instanceof \Carbon\Carbon 
                            ? $sale->sale_date 
                            : \Carbon\Carbon::parse($sale->sale_date);
                    @endphp
                    <tr class="sale-row" 
                        data-status="{{ $sale->status }}" 
                        data-date="{{ $saleDate->format('Y-m-d') }}"
                        data-timestamp="{{ $saleDate->timestamp }}">
                        <td class="sale-id">{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td class="customer-info">
                            <strong>{{ $sale->customer->name ?? 'Guest' }}</strong><br>
                            <small>{{ $sale->customer->email ?? 'No email' }}</small>
                        </td>
                        <td class="prescription-info">
                            @if($sale->prescription && $sale->prescription->file_path)
                            <a href="{{ asset('storage/' . $sale->prescription->file_path) }}"
                                target="_blank" class="prescription-link">
                                📄 Prescription #{{ $sale->prescription_id }}
                            </a>
                            @elseif($sale->prescription)
                            Prescription #{{ $sale->prescription_id }}
                            @else
                            <span style="color:#888;">N/A</span>
                            @endif
                        </td>
                        <td class="items-count">
                            <span class="items-badge">{{ $sale->items->count() }} items</span>
                            <small>{{ $sale->items->sum('quantity') }} total qty</small>
                        </td>
                        <td class="total-amount">₱{{ number_format($sale->total_amount, 2) }}</td>
                        <td class="payment-method">
                            <span class="payment-badge payment-{{ $sale->payment_method ?? 'cash' }}">
                                {{ ucfirst($sale->payment_method ?? 'cash') }}
                            </span>
                        </td>
                        <td class="status">
                            <span class="status-badge status-{{ $sale->status }}">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </td>
                        <td class="sale-date">
                            {{ $saleDate->format('M d, Y') }}<br>
                            <small>{{ $saleDate->format('h:i A') }}</small>
                        </td>
                        <td>
                            <button class="btn btn-primary view-details-btn" data-sale-id="{{ $sale->id }}">View Details</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Sale Details Modal -->
        <div id="saleDetailsModal" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>📋 Sale Details</h3>
                    <button class="close-btn" id="closeSaleModal">×</button>
                </div>
                <div id="saleDetailsContent">
                    <!-- Sale details will be dynamically loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFilter = document.getElementById('dateFilter');
            const saleRows = document.querySelectorAll('.sale-row');

            function filterSales() {
                const dateValue = dateFilter.value;
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const todayTimestamp = today.getTime() / 1000;

                saleRows.forEach(row => {
                    let showRow = true;
                    const rowTimestamp = parseInt(row.dataset.timestamp);

                    // Date Filter
                    switch (dateValue) {
                        case 'all':
                            showRow = true;
                            break;
                        case 'today':
                            const tomorrowTimestamp = todayTimestamp + (24 * 60 * 60);
                            showRow = rowTimestamp >= todayTimestamp && rowTimestamp < tomorrowTimestamp;
                            break;
                        case 'week':
                            const weekAgoTimestamp = todayTimestamp - (7 * 24 * 60 * 60);
                            showRow = rowTimestamp >= weekAgoTimestamp;
                            break;
                        case 'month':
                            const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                            const monthAgoTimestamp = monthAgo.getTime() / 1000;
                            showRow = rowTimestamp >= monthAgoTimestamp;
                            break;
                    }

                    row.style.display = showRow ? '' : 'none';
                });

                // Update visible count
                updateVisibleCount();
            }

            function updateVisibleCount() {
                const visibleRows = Array.from(saleRows).filter(row => row.style.display !== 'none');
                const filterValue = dateFilter.options[dateFilter.selectedIndex].text;
                
                // You can add a counter display here if needed
                console.log(`Showing ${visibleRows.length} sales for ${filterValue}`);
            }

            dateFilter.addEventListener('change', filterSales);

            // Sale Modal Logic
            const modal = document.getElementById('saleDetailsModal');
            const closeModal = document.getElementById('closeSaleModal');
            const viewBtns = document.querySelectorAll('.view-details-btn');

            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const saleId = this.dataset.saleId;
                    document.getElementById('saleDetailsContent').innerHTML = `<p>Loading details for sale #${saleId}...</p>`;
                    modal.style.display = 'block';
                    // You can add AJAX here to load sale details if needed
                });
            });

            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
    @stack('scripts')
</body>

</html>