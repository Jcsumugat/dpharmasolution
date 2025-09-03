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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        @php
                            // Ensure sale_date is a Carbon instance
                            $saleDate =
                                $sale->sale_date instanceof \Carbon\Carbon
                                    ? $sale->sale_date
                                    : \Carbon\Carbon::parse($sale->sale_date);
                        @endphp
                        <tr class="sale-row" data-status="{{ $sale->status }}"
                            data-date="{{ $saleDate->format('Y-m-d') }}" data-timestamp="{{ $saleDate->timestamp }}">
                            <td class="sale-id">{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="customer-info">
                                <strong>{{ $sale->customer->name ?? 'Guest' }}</strong><br>
                                <small>{{ $sale->customer->email ?? 'No email' }}</small>
                            </td>
                            <td class="prescription-info">
                                @if ($sale->prescription && $sale->prescription->file_path)
                                    <a href="{{ asset('storage/' . $sale->prescription->file_path) }}" target="_blank"
                                        class="prescription-link">
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Declare modal variables at function scope
            const modal = document.getElementById('saleDetailsModal');
            const closeModal = document.getElementById('closeSaleModal');
            const modalContent = document.getElementById('saleDetailsContent');

            // Debug: Check if elements exist
            console.log('Modal element:', modal);
            console.log('Close button:', closeModal);
            console.log('Modal content:', modalContent);

            // Debug: Check buttons
            const buttons = document.querySelectorAll('.view-details-btn');
            console.log('Found buttons:', buttons.length);

            function filterSales() {
                const dateValue = dateFilter.value;
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const todayTimestamp = today.getTime() / 1000;

                saleRows.forEach(row => {
                    let showRow = true;
                    const rowTimestamp = parseInt(row.dataset.timestamp);

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

                updateVisibleCount();
            }

            function updateVisibleCount() {
                const visibleRows = Array.from(saleRows).filter(row => row.style.display !== 'none');
                const filterValue = dateFilter.options[dateFilter.selectedIndex].text;
                console.log(`Showing ${visibleRows.length} sales for ${filterValue}`);
            }

            function showSaleDetails(saleId) {
                console.log('showSaleDetails called with ID:', saleId);

                if (!modal || !modalContent) {
                    console.error('Modal elements not found!');
                    alert('Modal not found. Check console for details.');
                    return;
                }

                // Show loading state
                modalContent.innerHTML = '<div class="loading">Loading sale details...</div>';
                modal.style.display = 'flex';

                console.log('Modal display set to:', modal.style.display);

                // Fetch sale details via AJAX
                fetch(`/admin/sales/${saleId}/details`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            renderSaleDetails(data.sale);
                        } else {
                            modalContent.innerHTML = `<div class="error">Error: ${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching sale details:', error);
                        modalContent.innerHTML =
                            '<div class="error">Failed to load sale details. Please try again.</div>';
                    });
            }

            function renderSaleDetails(sale) {
                const prescriptionSection = sale.prescription.file_path ?
                    `<a href="/storage/${sale.prescription.file_path}" target="_blank" class="prescription-link">
                📄 View Prescription File
            </a>` :
                    'No prescription file';

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
                    console.log('Button clicked, Sale ID:', saleId);
                    console.log('Button element:', e.target);

                    if (!saleId) {
                        console.error('No sale ID found on button');
                        return;
                    }

                    showSaleDetails(saleId);
                }
            });

            // Date filter event
            if (dateFilter) {
                dateFilter.addEventListener('change', filterSales);
            }

            // Modal close handlers
            if (closeModal) {
                closeModal.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    modal.style.display = 'none';
                    console.log('Modal closed via close button');
                });
            }

            // Click outside to close
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                        console.log('Modal closed via overlay click');
                    }
                });
            }

            // Keyboard support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
                    modal.style.display = 'none';
                    console.log('Modal closed via Escape key');
                }
            });
        });
    </script>
    @stack('scripts')
</body>

</html>
