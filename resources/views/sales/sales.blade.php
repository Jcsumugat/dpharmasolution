<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/sales.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    @include('admin.admin-header')
    <div class="content">
        <div class="sales-header">
            <h2>Sales Dashboard</h2>
            <div class="sales-filters">
                <select id="typeFilter" class="filter-select">
                    <option value="all">All Sales</option>
                    <option value="prescription">Prescription Orders</option>
                    <option value="walkin">Walk-in Sales</option>
                </select>
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
                <p class="summary-value">₱{{ number_format($salesStats['total_sales'] + $posStats['total_sales'], 2) }}</p>
                <small>{{ $salesStats['completed_count'] + $posStats['total_count'] }} completed transactions</small>
            </div>
            <div class="summary-card">
                <h3>Today's Sales</h3>
                <p class="summary-value">₱{{ number_format($salesStats['today_sales'] + $posStats['today_sales'], 2) }}</p>
                <small>{{ $salesStats['today_count'] + $posStats['today_count'] }} transactions today</small>
            </div>
            <div class="summary-card">
                <h3>Walk-in Sales</h3>
                <p class="summary-value">₱{{ number_format($posStats['total_sales'], 2) }}</p>
                <small>{{ $posStats['total_count'] }} walk-in transactions</small>
            </div>
            <div class="summary-card">
                <h3>Average Order</h3>
                <p class="summary-value">₱{{ number_format(($salesStats['total_sales'] + $posStats['total_sales']) / max(($salesStats['completed_count'] + $posStats['total_count']), 1), 2) }}</p>
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
                        <th>Order Type</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allSales = collect();

                        // Add prescription sales
                        foreach ($sales as $sale) {
                            $saleDate = $sale->sale_date instanceof \Carbon\Carbon ? $sale->sale_date : \Carbon\Carbon::parse($sale->sale_date ?? $sale->updated_at);
                            $allSales->push([
                                'type' => 'prescription',
                                'id' => $sale->id,
                                'transaction_id' => str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                                'customer_name' => $sale->customer ? $sale->customer->contact_number : ($sale->prescription && $sale->prescription->customer ? $sale->prescription->customer->contact_number : 'No contact'),
                                'customer_email' => $sale->customer ? ($sale->customer->email_address ?? 'No email') : ($sale->prescription && $sale->prescription->customer ? ($sale->prescription->customer->email_address ?? 'No email') : 'No email'),
                                'order_type' => $sale->prescription && $sale->prescription->order_type ? ucfirst($sale->prescription->order_type) : 'Regular',
                                'order_type_class' => $sale->prescription && $sale->prescription->order_type ? strtolower($sale->prescription->order_type) : 'regular',
                                'prescription_file' => $sale->prescription && $sale->prescription->file_path ? $sale->prescription->file_path : null,
                                'items_count' => $sale->items->count(),
                                'total_quantity' => $sale->items->sum('quantity'),
                                'total_amount' => $sale->total_amount,
                                'payment_method' => $sale->payment_method ?? 'cash',
                                'status' => $sale->status,
                                'date' => $saleDate,
                                'timestamp' => $saleDate->timestamp,
                                'data' => $sale
                            ]);
                        }

                        // Add POS transactions (walk-ins)
                        foreach ($posTransactions as $transaction) {
                            $transactionDate = \Carbon\Carbon::parse($transaction->created_at);
                            $allSales->push([
                                'type' => 'walkin',
                                'id' => $transaction->id,
                                'transaction_id' => $transaction->transaction_id,
                                'customer_name' => $transaction->customer_name ?: 'Walk-in Customer',
                                'customer_email' => 'Walk-in',
                                'order_type' => 'Walk-in',
                                'order_type_class' => 'walkin',
                                'prescription_file' => null,
                                'items_count' => $transaction->items->count(),
                                'total_quantity' => $transaction->items->sum('quantity'),
                                'total_amount' => $transaction->total_amount,
                                'payment_method' => $transaction->payment_method ?? 'cash',
                                'status' => $transaction->status,
                                'date' => $transactionDate,
                                'timestamp' => $transactionDate->timestamp,
                                'data' => $transaction
                            ]);
                        }

                        // Sort by date (newest first)
                        $allSales = $allSales->sortByDesc('timestamp');
                    @endphp

                    @forelse ($allSales as $sale)
                        <tr class="sale-row"
                            data-type="{{ $sale['type'] }}"
                            data-status="{{ $sale['status'] }}"
                            data-date="{{ $sale['date']->format('Y-m-d') }}"
                            data-timestamp="{{ $sale['timestamp'] }}">
                            <td class="sale-id">{{ $sale['transaction_id'] }}</td>
                            <td class="customer-info">
                                <strong>{{ $sale['customer_name'] }}</strong><br>
                                <small>{{ $sale['customer_email'] }}</small>
                            </td>
                            <td class="order-type-info">
                                <span class="order-type-badge order-type-{{ $sale['order_type_class'] }}">
                                    {{ $sale['order_type'] }}
                                </span>
                            </td>
                            <td class="items-count">
                                <span class="items-badge">{{ $sale['items_count'] }} items</span><br>
                                <small>{{ $sale['total_quantity'] }} total qty</small>
                            </td>
                            <td class="total_amount">₱{{ number_format($sale['total_amount'], 2) }}</td>
                            <td class="payment-method">
                                <span class="payment-badge payment-{{ $sale['payment_method'] }}">
                                    {{ ucfirst($sale['payment_method']) }}
                                </span>
                            </td>
                            <td class="sale-date">
                                {{ $sale['date']->format('d M, Y') }}<br>
                                <small>{{ $sale['date']->format('h:i A') }}</small>
                            </td>
                            <td>
                                <button class="view-details-btn"
                                    data-sale-id="{{ $sale['id'] }}"
                                    data-sale-type="{{ $sale['type'] }}">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No sales found</td>
                        </tr>
                    @endforelse
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
            const typeFilter = document.getElementById('typeFilter');
            const dateFilter = document.getElementById('dateFilter');
            const saleRows = document.querySelectorAll('.sale-row');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const modal = document.getElementById('saleDetailsModal');
            const closeModal = document.getElementById('closeSaleModal');
            const modalContent = document.getElementById('saleDetailsContent');

            function filterSales() {
                const typeValue = typeFilter.value;
                const dateValue = dateFilter.value;
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const todayTimestamp = today.getTime() / 1000;

                saleRows.forEach(row => {
                    let showRow = true;
                    const rowType = row.dataset.type;
                    const rowTimestamp = parseInt(row.dataset.timestamp);

                    // Type filter
                    if (typeValue !== 'all') {
                        showRow = rowType === typeValue;
                    }

                    // Date filter
                    if (showRow) {
                        switch (dateValue) {
                            case 'all':
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
                    }

                    row.style.display = showRow ? '' : 'none';
                });
            }

            function showSaleDetails(saleId, saleType) {
                if (!modal || !modalContent) {
                    console.error('Modal elements not found!');
                    return;
                }

                modalContent.innerHTML = '<div class="loading">Loading sale details...</div>';
                modal.style.display = 'flex';

                const endpoint = saleType === 'walkin'
                    ? `/admin/sales/pos/${saleId}/details`
                    : `/admin/sales/${saleId}/details`;

                fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (saleType === 'walkin') {
                            renderPOSDetails(data.transaction);
                        } else {
                            renderSaleDetails(data.sale);
                        }
                    } else {
                        modalContent.innerHTML = `<div class="error">Error: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching sale details:', error);
                    modalContent.innerHTML = '<div class="error">Failed to load sale details. Please try again.</div>';
                });
            }

            function renderPOSDetails(transaction) {
                const notesSection = transaction.notes ?
                    `<div class="detail-item">
                        <label>Notes</label>
                        <span>${transaction.notes}</span>
                    </div>` : '';

                const itemsHTML = transaction.items.map((item, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `).join('');

                modalContent.innerHTML = `
                    <div class="sale-details">
                        <div class="detail-section">
                            <h4>📋 Transaction Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Transaction ID</label>
                                    <span>${transaction.transaction_id}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Type</label>
                                    <span class="order-type-badge order-type-walkin">Walk-in</span>
                                </div>
                                <div class="detail-item">
                                    <label>Status</label>
                                    <span class="status-badge status-${transaction.status}">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Date</label>
                                    <span>${transaction.created_at}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4>👤 Customer Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Customer Name</label>
                                    <span>${transaction.customer_name || 'Walk-in Customer'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Customer Type</label>
                                    <span>${transaction.customer_type || 'Walk-in'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4>🛒 Items Purchased</h4>
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHTML}
                                </tbody>
                            </table>
                        </div>

                        <div class="detail-section">
                            <h4>💳 Payment Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Subtotal</label>
                                    <span>₱${parseFloat(transaction.subtotal).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Discount</label>
                                    <span>₱${parseFloat(transaction.discount_amount).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Total Amount</label>
                                    <span style="font-size: 1.3em; font-weight: bold; color: #27ae60;">₱${parseFloat(transaction.total_amount).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Amount Paid</label>
                                    <span>₱${parseFloat(transaction.amount_paid).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Change</label>
                                    <span>₱${parseFloat(transaction.change_amount).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Payment Method</label>
                                    <span class="payment-badge payment-${transaction.payment_method}">
                                        ${transaction.payment_method.charAt(0).toUpperCase() + transaction.payment_method.slice(1)}
                                    </span>
                                </div>
                                ${notesSection}
                            </div>
                        </div>
                    </div>
                `;
            }

            function renderSaleDetails(sale) {
                const prescriptionSection = sale.prescription.file_path ?
                    `<a href="/storage/${sale.prescription.file_path}" target="_blank" class="prescription-link">
                        📄 View Prescription File
                    </a>` : 'No prescription file';

                const notesSection = sale.notes ?
                    `<div class="detail-item">
                        <label>Notes</label>
                        <span>${sale.notes}</span>
                    </div>` : '';

                const itemsHTML = sale.items.map((item, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>₱${parseFloat(item.subtotal).toFixed(2)}</td>
                    </tr>
                `).join('');

                modalContent.innerHTML = `
                    <div class="sale-details">
                        <div class="detail-section">
                            <h4>📋 Sale Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Sale ID</label>
                                    <span>${String(sale.id).padStart(6, '0')}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Order ID</label>
                                    <span>${sale.order_id}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Status</label>
                                    <span class="status-badge status-${sale.status}">${sale.status.charAt(0).toUpperCase() + sale.status.slice(1)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Sale Date</label>
                                    <span>${sale.sale_date}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4>👤 Customer Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Name</label>
                                    <span>${sale.customer.name}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Email</label>
                                    <span>${sale.customer.email}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Phone</label>
                                    <span>${sale.customer.phone}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4>🏥 Prescription</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Prescription ID</label>
                                    <span>#${sale.prescription.id}</span>
                                </div>
                                <div class="detail-item">
                                    <label>File</label>
                                    <span>${prescriptionSection}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4>🛒 Items Purchased</h4>
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHTML}
                                    <tr class="total-row">
                                        <td colspan="4"><strong>Total</strong></td>
                                        <td><strong>₱${parseFloat(sale.total_amount).toFixed(2)}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="detail-section">
                            <h4>💳 Payment Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Payment Method</label>
                                    <span class="payment-badge payment-${sale.payment_method}">
                                        ${sale.payment_method.charAt(0).toUpperCase() + sale.payment_method.slice(1)}
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <label>Total Amount</label>
                                    <span style="font-size: 1.3em; font-weight: bold; color: #27ae60;">₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Total Items</label>
                                    <span>${sale.total_items}</span>
                                </div>
                                ${notesSection}
                            </div>
                        </div>
                    </div>
                `;
            }

            // Event delegation for view details buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-details-btn')) {
                    e.preventDefault();
                    e.stopPropagation();

                    const saleId = e.target.dataset.saleId;
                    const saleType = e.target.dataset.saleType;

                    if (!saleId) {
                        console.error('No sale ID found on button');
                        return;
                    }

                    showSaleDetails(saleId, saleType);
                }
            });

            // Filter event listeners
            if (typeFilter) {
                typeFilter.addEventListener('change', filterSales);
            }
            if (dateFilter) {
                dateFilter.addEventListener('change', filterSales);
            }

            // Modal close handlers
            if (closeModal) {
                closeModal.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    modal.style.display = 'none';
                });
            }

            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
    @stack('scripts')
</body>
@include('admin.admin-footer')
</html>
