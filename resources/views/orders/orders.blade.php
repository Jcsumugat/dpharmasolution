<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Orders Management</title>
    <link rel="stylesheet" href="{{ asset('css/orders.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    @include('admin.admin-header')

    <div class="content">
        <div class="page-title" style="display: flex;  margin-bottom: 20px;">
            <h2>Orders Management</h2>
            <div class="order-type-filters">
                <button class="filter-btn active" data-filter="all">All Orders</button>
                <button class="filter-btn" data-filter="prescription">Prescriptions</button>
                <button class="filter-btn" data-filter="online_order">Non Prescriptions</button>
            </div>
        </div>

        @foreach (['success', 'info', 'error'] as $msg)
            @if (session($msg))
                <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }}">{{ session($msg) }}</div>
            @endif
        @endforeach

        <div class="order-stats">
            <div class="stat-card">
                <div class="stat-number" id="total-orders">{{ $prescriptions->count() }}</div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="pending-orders">{{ $prescriptions->where('status', 'pending')->count() }}
                </div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="prescription-count">
                    {{ $prescriptions->where('order_type', 'prescription')->count() }}</div>
                <div class="stat-label">Prescriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="online-order-count">
                    {{ $prescriptions->where('order_type', 'online_order')->count() }}</div>
                <div class="stat-label">Non Prescription Orders</div>
            </div>
        </div>

        <div class="search-container">
            <input type="text" class="search-input" id="order-search"
                placeholder="Search by Order ID, Customer, Mobile, or Notes...">
            <select class="filter-btn" id="status-filter">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order Info</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Order Details</th>
                        <th>Document</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body">
                    @foreach ($prescriptions as $prescription)
                        <tr class="order-row" data-order-type="{{ $prescription->order_type ?? 'prescription' }}"
                            data-status="{{ strtolower($prescription->status ?? 'pending') }}"
                            data-search="{{ strtolower($prescription->order->order_id ?? '') }} {{ strtolower($prescription->customer->email_address ?? '') }} {{ strtolower($prescription->mobile_number ?? '') }} {{ strtolower($prescription->notes ?? '') }}">

                            <td>
                                <strong>{{ $prescription->order->order_id ?? 'ORD-' . $prescription->id }}</strong>
                                <span class="order-type-badge {{ $prescription->order_type ?? 'prescription' }}">
                                    {{ $prescription->order_type === 'online_order' ? 'Medicine Order' : 'Prescription' }}
                                </span>
                                <div class="order-meta">
                                    <span
                                        class="priority-indicator priority-{{ $prescription->created_at->diffInHours() > 24 ? 'low' : ($prescription->created_at->diffInHours() > 4 ? 'medium' : 'high') }}"></span>
                                    {{ $prescription->created_at->diffForHumans() }}
                                </div>
                            </td>

                            <td>
                                @if ($prescription->customer)
                                    <div><strong>I D: {{ $prescription->customer->customer_id }}</strong></div>
                                    <div><small>{{ $prescription->customer->email_address ?? 'N/A' }}</small></div>
                                @else
                                    <em>Guest Order</em>
                                @endif
                                <div class="message-indicator" id="msg-indicator-{{ $prescription->id }}">
                                    @if ($prescription->unreadMessagesForAdmin()->count() > 0)
                                        <span
                                            class="unread-indicator">{{ $prescription->unreadMessagesForAdmin()->count() }}
                                            new message</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div>{{ $prescription->mobile_number }}</div>
                                @if ($prescription->customer && $prescription->customer->address)
                                    <div><small>{{ Str::limit($prescription->customer->address, 30) }}</small></div>
                                @endif
                            </td>

                            <td>
                                <div class="status-container">
                                    <span class="status-badge {{ strtolower($prescription->status ?? 'pending') }}">
                                        {{ ucfirst($prescription->status ?? 'Pending') }}
                                    </span>
                                    <div class="status-info-icon"
                                        data-status="{{ strtolower($prescription->status ?? 'pending') }}"
                                        data-order-type="{{ $prescription->order_type ?? 'prescription' }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="2" />
                                            <path d="m9,9 0,0 A3,3 0 0,1 15,9 A3.5,3.5 0 0,1 12,12.5"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="12" cy="16" r="1" fill="currentColor" />
                                        </svg>
                                        <div class="status-tooltip"></div>
                                    </div>
                                </div>
                                @if ($prescription->notes)
                                    <div class="order-meta">
                                        <strong>Notes:</strong> {{ Str::limit($prescription->notes, 50) }}
                                    </div>
                                @endif
                                @if ($prescription->order_type === 'online_order')
                                    <div class="order-meta">Direct medicine order - no prescription validation required
                                    </div>
                                @else
                                    <div class="order-meta">Requires pharmacist prescription review</div>
                                @endif
                            </td>

                            <td>
                                @if ($prescription->is_encrypted && $prescription->file_path)
                                    <div class="prescription-file-info encrypted-file">
                                        <strong>{{ $prescription->original_filename ?? 'Encrypted File' }}</strong>
                                        <span class="security-badge">ENCRYPTED</span>
                                        @if ($prescription->file_size)
                                            <div class="file-size">Size:
                                                {{ number_format($prescription->file_size / 1024, 1) }} KB</div>
                                        @endif
                                        <div class="file-actions">
                                            @if (str_starts_with($prescription->file_mime_type ?? '', 'image/'))
                                                <a href="{{ route('prescription.file.view', $prescription->id) }}"
                                                    target="_blank" class="btn-file btn-view">View Image</a>
                                            @endif
                                            <a href="{{ route('prescription.file.download', $prescription->id) }}"
                                                class="btn-file btn-download">Download</a>
                                        </div>
                                        <div style="font-size: 0.75em; color: #6c757d; margin-top: 2px;">Type:
                                            {{ $prescription->file_mime_type ?? 'Unknown' }}</div>
                                    </div>
                                @elseif($prescription->file_path)
                                    <div class="prescription-file-info legacy-file">
                                        <strong>Legacy File</strong>
                                        <span
                                            style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold;">UNENCRYPTED</span>
                                        <div class="file-actions">
                                            <a href="{{ asset('storage/' . $prescription->file_path) }}"
                                                target="_blank" class="btn-file btn-view">View File</a>
                                        </div>
                                        <div style="font-size: 0.75em; color: #856404; margin-top: 2px;">Uploaded
                                            before
                                            encryption</div>
                                    </div>
                                @else
                                    <span class="no-file">No document</span>
                                @endif
                            </td>

                            <td>{{ $prescription->created_at->format('M d, Y') }}<br><small>{{ $prescription->created_at->format('H:i') }}</small>
                            </td>

                            <td class="action-cell">
                                <div class="action-dropdown">
                                    <button class="dropdown-trigger"
                                        data-id="{{ $prescription->id }}">&#8230;</button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item manage manage-order-btn"
                                            data-id="{{ $prescription->id }}">Manage Products</button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item chat chat-order-btn"
                                            data-id="{{ $prescription->id }}">Message Customer</button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item status-action manage-status-btn"
                                            data-id="{{ $prescription->id }}">Manage Status</button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item complete complete-order-btn"
                                            data-id="{{ $prescription->id }}">Complete Order</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="dropdown-overlay"></div>

        <div id="manageStatusModal" class="modal">
            <div class="modal-content">
                <h3>Manage Order Status</h3>
                <form id="statusForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="statusPrescriptionId" />

                    <label for="statusSelect">Status:</label>
                    <select id="statusSelect" name="status" class="dropdown" required>
                        <option value="">-- Select Status --</option>
                        <option value="approve">Approve Order</option>
                        <option value="cancel">Cancel Order</option>
                    </select>

                    <label for="reasonMessage">Reason/Message:</label>
                    <textarea id="reasonMessage" name="message" rows="4" placeholder="Enter reason or message for the customer..."
                        required></textarea>

                    <div class="modal-actions">
                        <button type="button" id="cancelStatusModal" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="manageOrderModal" class="modal">
            <div class="modal-content">
                <h3>Manage Order Products</h3>
                <input type="text" id="productSearch" placeholder="Search products..." />
                <div style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <h4>Available Products</h4>
                        <ul id="availableProducts">
                            @foreach ($products as $product)
                                @php
                                    $totalStock = $product->batches ? $product->stock_quantity : 0;
                                    $latestBatch = $product->batches
                                        ? $product->batches->sortByDesc('id')->first()
                                        : null;
                                    $salePrice = $latestBatch ? $latestBatch->sale_price : 0;
                                @endphp
                                <li data-name="{{ strtolower($product->product_name) }}"
                                    data-id="{{ $product->id }}" data-product="{{ $product->product_name }}"
                                    data-price="{{ $salePrice }}" data-stock="{{ $totalStock }}">
                                    {{ $product->product_name }} ({{ $product->brand_name ?? 'No Brand' }}) -
                                    ₱{{ number_format($salePrice, 2) }} | Stock: {{ $totalStock }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div style="flex: 1;">
                        <h4>Selected Products</h4>
                        <ul id="selectedProducts"></ul>
                    </div>
                </div>
                <div>
                    <button class="btn btn-secondary" id="cancelManageOrder">Cancel</button>
                    <button id="saveSelection" class="btn btn-primary">Save Selection</button>
                </div>
            </div>
        </div>

        <div id="productQuantityModal" class="modal">
            <div class="modal-content">
                <h3 id="productModalName"></h3>
                <div class="quantity-controls">
                    <button id="decreaseQty">−</button>
                    <input type="number" id="productQty" value="1" min="1" />
                    <button id="increaseQty">+</button>
                </div>
                <div>
                    <button class="btn btn-secondary" id="cancelQtyModal">Cancel</button>
                    <button class="btn btn-primary" id="confirmQtyModal">Select</button>
                </div>
            </div>
        </div>

        <div id="completeOrderModal" class="modal-overlay">
            <div class="modal-content">
                <h3>Confirm Order Completion</h3>
                <div id="orderSummary">
                    <p>Selected products will be displayed here...</p>
                </div>
                <div class="modal-buttons">
                    <button class="btn btn-primary" id="submitOrderBtn">Complete Order</button>
                    <button class="btn btn-secondary" id="cancelCompleteModal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div id="chatModal" class="chat-modal">
        <div class="chat-container">
            <div class="chat-header">
                <div class="chat-title">
                    <span id="chatOrderId">Order Chat</span>
                </div>
                <button class="chat-close" id="closeChatModal">&times;</button>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="no-messages">No messages yet. Start the conversation!</div>
            </div>
            <div class="chat-input-container">
                <textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1"></textarea>
                <button class="chat-send" id="sendMessage">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2 21l21-9L2 3v7l15 2-15 2v7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Global variables - declared once at the top level
            let currentOrderPrescriptionId = null;
            let currentPrescriptionId = null;
            let selectedProductLi = null;
            const selectedProductsByOrder = {};

            // DOM elements
            const dropdownOverlay = document.querySelector('.dropdown-overlay');
            const statusModal = document.getElementById('manageStatusModal');
            const manageModal = document.getElementById('manageOrderModal');
            const qtyModal = document.getElementById('productQuantityModal');
            const completeOrderModal = document.getElementById('completeOrderModal');

            // Form elements
            const statusForm = document.getElementById('statusForm');
            const statusPrescriptionId = document.getElementById('statusPrescriptionId');
            const statusSelect = document.getElementById('statusSelect');
            const reasonMessage = document.getElementById('reasonMessage');

            // Product management elements
            const availableList = document.getElementById('availableProducts');
            const selectedList = document.getElementById('selectedProducts');
            const productSearchInput = document.getElementById('productSearch');
            const qtyInput = document.getElementById('productQty');
            const productModalName = document.getElementById('productModalName');

            // Order completion elements
            const orderSummary = document.getElementById('orderSummary');
            const submitOrderBtn = document.getElementById('submitOrderBtn');

            // Search and filter elements
            const searchInput = document.getElementById('order-search');
            const statusFilter = document.getElementById('status-filter');
            const typeFilters = document.querySelectorAll('.filter-btn[data-filter]');

            // Status tooltip configuration
            const statusMessages = {
                'pending': {
                    'prescription': 'This order has been placed and prescription document needs to be validated before processing.',
                    'online_order': 'This medicine order is pending review. Direct orders don\'t require prescription validation but need approval before fulfillment.'
                },
                'approved': {
                    'prescription': 'This prescription has been reviewed and approved by a licensed pharmacist. The order can now be prepared and fulfilled.',
                    'online_order': 'This medicine order has been approved and is ready for preparation. Products can now be allocated and prepared for delivery.'
                },
                'completed': {
                    'prescription': 'This prescription order has been successfully fulfilled and completed. All medications have been dispensed.',
                    'online_order': 'This medicine order has been completed successfully. All ordered products have been delivered or picked up.'
                },
            };

            // Utility functions
            function getCSRFToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            }

            function closeAllDropdowns() {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                if (dropdownOverlay) {
                    dropdownOverlay.style.display = 'none';
                }
            }

            function closeAllModals() {
                [statusModal, manageModal, qtyModal, completeOrderModal].forEach(modal => {
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('active');
                    }
                });
                currentOrderPrescriptionId = null;
                currentPrescriptionId = null;
                selectedProductLi = null;
            }

            // Initialize status tooltips
            function initializeTooltips() {
                const statusIcons = document.querySelectorAll('.status-info-icon');

                statusIcons.forEach(icon => {
                    const status = icon.dataset.status;
                    const orderType = icon.dataset.orderType;
                    const tooltip = icon.querySelector('.status-tooltip');

                    if (tooltip) {
                        const message = statusMessages[status]?.[orderType] ||
                            statusMessages[status]?.['prescription'] ||
                            'Status information not available.';
                        tooltip.textContent = message;
                    }
                });

                statusIcons.forEach(icon => {
                    icon.addEventListener('click', function(e) {
                        e.stopPropagation();

                        // Hide other tooltips
                        statusIcons.forEach(otherIcon => {
                            if (otherIcon !== icon) {
                                const otherTooltip = otherIcon.querySelector(
                                    '.status-tooltip');
                                if (otherTooltip) {
                                    otherTooltip.style.opacity = '0';
                                    otherTooltip.style.visibility = 'hidden';
                                }
                            }
                        });

                        // Toggle current tooltip
                        const tooltip = this.querySelector('.status-tooltip');
                        if (tooltip) {
                            const isVisible = tooltip.style.opacity === '1';

                            if (isVisible) {
                                tooltip.style.opacity = '0';
                                tooltip.style.visibility = 'hidden';
                            } else {
                                tooltip.style.opacity = '1';
                                tooltip.style.visibility = 'visible';

                                setTimeout(() => {
                                    tooltip.style.opacity = '0';
                                    tooltip.style.visibility = 'hidden';
                                }, 3000);
                            }
                        }
                    });
                });

                // Hide tooltips when clicking elsewhere
                document.addEventListener('click', function() {
                    statusIcons.forEach(icon => {
                        const tooltip = icon.querySelector('.status-tooltip');
                        if (tooltip) {
                            tooltip.style.opacity = '0';
                            tooltip.style.visibility = 'hidden';
                        }
                    });
                });
            }

            // Filter and search functionality
            function filterOrders() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const statusFilterValue = statusFilter ? statusFilter.value : 'all';
                const activeTypeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';

                document.querySelectorAll('.order-row').forEach(row => {
                    const searchData = row.dataset.search || '';
                    const orderStatus = row.dataset.status;
                    const orderType = row.dataset.orderType;

                    let show = true;

                    if (searchTerm && !searchData.includes(searchTerm)) {
                        show = false;
                    }

                    if (statusFilterValue !== 'all' && orderStatus !== statusFilterValue) {
                        show = false;
                    }

                    if (activeTypeFilter !== 'all' && orderType !== activeTypeFilter) {
                        show = false;
                    }

                    row.style.display = show ? '' : 'none';
                });
            }

            // Initialize search and filters
            if (searchInput) {
                searchInput.addEventListener('input', filterOrders);
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', filterOrders);
            }

            typeFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    typeFilters.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    filterOrders();
                });
            });

            // Dropdown functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('dropdown-trigger')) {
                    e.stopPropagation();

                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });

                    const dropdown = e.target.nextElementSibling;
                    if (dropdown) {
                        dropdown.classList.toggle('show');

                        if (dropdown.classList.contains('show') && dropdownOverlay) {
                            dropdownOverlay.style.display = 'block';
                        }
                    }
                }
            });

            if (dropdownOverlay) {
                dropdownOverlay.addEventListener('click', closeAllDropdowns);
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.action-dropdown')) {
                    closeAllDropdowns();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape") {
                    closeAllDropdowns();
                    closeAllModals();
                }
            });

            // Status management modal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('manage-status-btn')) {
                    closeAllDropdowns();
                    const id = e.target.dataset.id;

                    if (statusPrescriptionId) statusPrescriptionId.value = id;
                    if (statusSelect) statusSelect.selectedIndex = 0;
                    if (reasonMessage) reasonMessage.value = '';

                    if (statusModal) statusModal.style.display = 'flex';
                }
            });

            if (statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const id = statusPrescriptionId ? statusPrescriptionId.value : '';
                    const action = statusSelect ? statusSelect.value : '';
                    const message = reasonMessage ? reasonMessage.value.trim() : '';

                    if (!action) {
                        alert('Please select a status.');
                        return;
                    }

                    if (!message) {
                        alert('Please enter a reason or message.');
                        return;
                    }

                    const submitBtn = statusForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn ? submitBtn.textContent : 'Submit';
                    if (submitBtn) {
                        submitBtn.textContent = 'Processing...';
                        submitBtn.disabled = true;
                    }

                    const formData = new FormData();
                    formData.append('_token', getCSRFToken());
                    formData.append('message', message);

                    fetch(`/admin/orders/${id}/${action}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': getCSRFToken(),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error('Server error:', text);
                                    throw new Error(`Server error: ${response.status}`);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                alert(data.message || 'Action completed successfully!');
                                if (statusModal) statusModal.style.display = 'none';
                                window.location.reload();
                            } else {
                                alert(data.message || 'Action failed!');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred: ' + error.message);
                        })
                        .finally(() => {
                            if (submitBtn) {
                                submitBtn.textContent = originalText;
                                submitBtn.disabled = false;
                            }
                        });
                });
            }

            document.getElementById('cancelStatusModal')?.addEventListener('click', () => {
                if (statusModal) statusModal.style.display = 'none';
                if (statusSelect) statusSelect.selectedIndex = 0;
                if (reasonMessage) reasonMessage.value = '';
            });

            // Product management functionality
            if (productSearchInput) {
                productSearchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('#availableProducts li').forEach(li => {
                        const productName = li.dataset.name || '';
                        li.style.display = productName.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('manage-order-btn')) {
                    closeAllDropdowns();
                    currentPrescriptionId = e.target.dataset.id;
                    if (manageModal) manageModal.style.display = 'flex';
                    loadSavedProducts(currentPrescriptionId);
                }
            });

            document.getElementById('cancelManageOrder')?.addEventListener('click', () => {
                if (manageModal) manageModal.style.display = 'none';
            });

            if (availableList) {
                availableList.addEventListener('click', e => {
                    if (e.target.tagName === 'LI') {
                        const stockLevel = parseInt(e.target.dataset.stock) || 0;
                        if (stockLevel <= 0) {
                            alert('This product is out of stock.');
                            return;
                        }

                        selectedProductLi = e.target;
                        if (qtyInput) {
                            qtyInput.value = 1;
                            qtyInput.max = stockLevel;
                        }
                        if (productModalName) {
                            productModalName.textContent = selectedProductLi.dataset.product || '';
                        }
                        if (qtyModal) qtyModal.style.display = 'flex';
                    }
                });
            }

            document.getElementById('cancelQtyModal')?.addEventListener('click', () => {
                if (qtyModal) qtyModal.style.display = 'none';
                selectedProductLi = null;
            });

            document.getElementById('increaseQty')?.addEventListener('click', () => {
                if (selectedProductLi && qtyInput) {
                    const currentQty = parseInt(qtyInput.value) || 1;
                    const maxStock = parseInt(selectedProductLi.dataset.stock) || 0;
                    if (currentQty < maxStock) {
                        qtyInput.value = currentQty + 1;
                    } else {
                        alert(`Maximum stock available: ${maxStock}`);
                    }
                }
            });

            document.getElementById('decreaseQty')?.addEventListener('click', () => {
                if (qtyInput && qtyInput.value > 1) {
                    qtyInput.value = parseInt(qtyInput.value) - 1;
                }
            });

            if (qtyInput) {
                qtyInput.addEventListener('input', () => {
                    const currentQty = parseInt(qtyInput.value) || 1;
                    const maxStock = parseInt(selectedProductLi?.dataset.stock || 999);

                    if (currentQty < 1) {
                        qtyInput.value = 1;
                    } else if (currentQty > maxStock) {
                        qtyInput.value = maxStock;
                        alert(`Maximum stock available: ${maxStock}`);
                    }
                });
            }

            document.getElementById('confirmQtyModal')?.addEventListener('click', () => {
                if (!selectedProductLi || !qtyInput) return;

                const quantity = parseInt(qtyInput.value) || 1;
                const id = selectedProductLi.dataset.id;
                const name = selectedProductLi.dataset.product;
                const price = selectedProductLi.dataset.price;
                const maxStock = parseInt(selectedProductLi.dataset.stock) || 0;

                if (quantity > maxStock) {
                    alert(`Cannot select ${quantity} items. Only ${maxStock} available in stock.`);
                    return;
                }

                if (!selectedProductsByOrder[currentPrescriptionId]) {
                    selectedProductsByOrder[currentPrescriptionId] = [];
                }

                const exists = selectedProductsByOrder[currentPrescriptionId].find(p => p.id === id);
                if (!exists) {
                    selectedProductsByOrder[currentPrescriptionId].push({
                        id: id,
                        name: name,
                        price: price,
                        quantity: quantity
                    });
                    updateSelectedProductsDisplay();
                } else {
                    alert('Product already selected!');
                }

                if (qtyModal) qtyModal.style.display = 'none';
                selectedProductLi = null;
            });

            function loadSavedProducts(prescriptionId) {
                if (!prescriptionId || !selectedList) return;

                selectedList.innerHTML = '<li>Loading saved products...</li>';

                const csrfToken = getCSRFToken();

                fetch(`/prescriptions/${prescriptionId}/items`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.items) {
                            selectedProductsByOrder[prescriptionId] = [];
                            data.items.forEach(item => {
                                selectedProductsByOrder[prescriptionId].push({
                                    id: item.product_id.toString(),
                                    name: item.product_name,
                                    price: item.product_price,
                                    quantity: item.quantity
                                });
                            });
                            updateSelectedProductsDisplay();
                        } else {
                            selectedProductsByOrder[prescriptionId] = [];
                            updateSelectedProductsDisplay();
                        }
                    })
                    .catch(error => {
                        console.error('Error loading saved products:', error);
                        selectedProductsByOrder[prescriptionId] = [];
                        updateSelectedProductsDisplay();
                    });
            }

            function updateSelectedProductsDisplay() {
                if (!selectedList) return;

                selectedList.innerHTML = '';
                const selectedProducts = selectedProductsByOrder[currentPrescriptionId] || [];

                document.querySelectorAll('#availableProducts li').forEach(li => li.style.display = '');

                selectedProducts.forEach(product => {
                    const availableLi = document.querySelector(
                        `#availableProducts li[data-id="${product.id}"]`);
                    if (availableLi) availableLi.style.display = 'none';

                    const li = document.createElement('li');
                    li.dataset.id = product.id;
                    li.innerHTML = `
                        <span>${product.name} — Qty: ${product.quantity}</span>
                        <button class="remove-btn" data-id="${product.id}">❌</button>
                    `;
                    selectedList.appendChild(li);
                });
            }

            if (selectedList) {
                selectedList.addEventListener('click', e => {
                    if (e.target.classList.contains('remove-btn')) {
                        const productId = e.target.dataset.id;
                        if (selectedProductsByOrder[currentPrescriptionId]) {
                            selectedProductsByOrder[currentPrescriptionId] = selectedProductsByOrder[
                                currentPrescriptionId].filter(p => p.id !== productId);
                            updateSelectedProductsDisplay();
                        }
                    }
                });
            }

            // Save selection handler
            document.getElementById('saveSelection')?.addEventListener('click', function(e) {
                e.preventDefault();

                const selectedProducts = selectedProductsByOrder[currentPrescriptionId] || [];

                if (!selectedProducts.length) {
                    alert("No products selected.");
                    return;
                }

                const saveBtn = document.getElementById('saveSelection');
                const originalText = saveBtn ? saveBtn.textContent : 'Save Selection';
                if (saveBtn) {
                    saveBtn.textContent = 'Saving...';
                    saveBtn.disabled = true;
                }

                const csrfToken = getCSRFToken();

                fetch(`/prescriptions/${currentPrescriptionId}/save-selection`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            items: selectedProducts.map(item => ({
                                product_id: parseInt(item.id),
                                quantity: parseInt(item.quantity),
                                price: parseFloat(item.price),
                                name: item.name
                            }))
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server error:', text);
                                throw new Error(`Server error: ${response.status} - ${text}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Products saved successfully!');
                            if (manageModal) manageModal.style.display = 'none';
                        } else {
                            alert(data.message || 'Failed to save products. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving products:', error);
                        alert('An error occurred while saving products: ' + error.message);
                    })
                    .finally(() => {
                        if (saveBtn) {
                            saveBtn.textContent = originalText;
                            saveBtn.disabled = false;
                        }
                    });
            });

            // Complete order functionality
            document.addEventListener('click', async function(e) {
                if (e.target.classList.contains('complete-order-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeAllDropdowns();

                    const prescriptionId = e.target.dataset.id;
                    currentOrderPrescriptionId = prescriptionId;

                    try {
                        const csrfToken = getCSRFToken();

                        const response = await fetch(`/orders/${prescriptionId}/summary`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (orderSummary) {
                            if (data.success && data.items && data.items.length > 0) {
                                let summaryHTML = '<h4>Order Summary:</h4>';
                                summaryHTML += '<div class="order-items">';

                                data.items.forEach(item => {
                                    const itemTotal = item.quantity * item.unit_price;
                                    summaryHTML += `
                                        <div class="order-item">
                                            <strong>${item.product_name}</strong><br>
                                            <span>Quantity: ${item.quantity} | Unit Price: ₱${parseFloat(item.unit_price).toFixed(2)}</span><br>
                                            <span>Subtotal: ₱${itemTotal.toFixed(2)}</span>
                                            ${item.stock_available < item.quantity ? '<br><span class="low-stock">⚠️ Low Stock</span>' : ''}
                                        </div>
                                    `;
                                });

                                summaryHTML += '</div>';
                                summaryHTML += `
                                    <div class="order-total">
                                        <strong>Total Items: ${data.total_items}</strong><br>
                                        <strong>Total Amount: ₱${parseFloat(data.total_amount).toFixed(2)}</strong>
                                    </div>
                                `;

                                summaryHTML += `
                                    <div class="payment-section">
                                        <label for="paymentMethod"><strong>Payment Method:</strong></label>
                                        <select id="paymentMethod">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="gcash">GCash</option>
                                            <option value="online">Online Banking</option>
                                        </select>
                                    </div>
                                `;

                                summaryHTML += `
                                    <div class="notes-section">
                                        <label for="orderNotes"><strong>Notes (optional):</strong></label>
                                        <textarea id="orderNotes" rows="3" placeholder="Additional notes for this order..."></textarea>
                                    </div>
                                `;

                                orderSummary.innerHTML = summaryHTML;
                            } else {
                                orderSummary.innerHTML =
                                    '<p>No products selected yet. Please manage the order first.</p>';
                            }
                        }

                        if (completeOrderModal) {
                            completeOrderModal.style.display = 'block';
                            completeOrderModal.classList.add('active');
                        }

                    } catch (error) {
                        console.error('Error loading order summary:', error);
                        alert(
                            `Error loading order summary: ${error.message}. Please ensure products are saved first.`
                        );
                    }
                }
            });

            // Submit order completion
            if (submitOrderBtn) {
                submitOrderBtn.addEventListener('click', async (e) => {
                    e.preventDefault();

                    if (!currentOrderPrescriptionId) {
                        alert('Error: No prescription selected. Please try again.');
                        return;
                    }

                    const originalText = submitOrderBtn.textContent;
                    submitOrderBtn.textContent = 'Processing...';
                    submitOrderBtn.disabled = true;

                    try {
                        const csrfToken = getCSRFToken();

                        if (!csrfToken) {
                            throw new Error(
                                'CSRF token not found. Please ensure the CSRF meta tag is in your HTML head.'
                            );
                        }

                        const paymentMethodElement = document.getElementById('paymentMethod');
                        const orderNotesElement = document.getElementById('orderNotes');

                        const paymentMethod = paymentMethodElement ? paymentMethodElement.value :
                            'cash';
                        const orderNotes = orderNotesElement ? orderNotesElement.value.trim() : '';

                        const response = await fetch(`/orders/${currentOrderPrescriptionId}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                payment_method: paymentMethod,
                                notes: orderNotes
                            })
                        });

                        let data;
                        const responseText = await response.text();

                        try {
                            data = JSON.parse(responseText);
                        } catch (parseError) {
                            throw new Error(
                                `Server returned invalid JSON. Status: ${response.status}, Response: ${responseText.substring(0, 200)}...`
                            );
                        }

                        if (response.ok && data.success) {
                            alert(
                                `Order completed successfully!\n\nSale ID: ${data.sale_id}\nTotal Amount: ₱${parseFloat(data.total_amount).toFixed(2)}\nTotal Items: ${data.total_items}\nPayment Method: ${data.payment_method}\n\nStock has been updated automatically.`
                            );

                            if (completeOrderModal) {
                                completeOrderModal.style.display = 'none';
                                completeOrderModal.classList.remove('active');
                            }
                            currentOrderPrescriptionId = null;

                            window.location.reload();
                        } else {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        }

                    } catch (error) {
                        console.error('Error completing order:', error);
                        alert(`Failed to complete order: ${error.message}`);
                    } finally {
                        submitOrderBtn.textContent = originalText;
                        submitOrderBtn.disabled = false;
                    }
                });
            }

            // Modal close handlers
            document.getElementById('cancelCompleteModal')?.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (completeOrderModal) {
                    completeOrderModal.style.display = 'none';
                    completeOrderModal.classList.remove('active');
                }
                currentOrderPrescriptionId = null;
            });

            // Close modals when clicking outside
            [statusModal, manageModal, qtyModal, completeOrderModal].forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === e.currentTarget) {
                            modal.style.display = 'none';
                            modal.classList.remove('active');

                            if (modal === manageModal) {
                                currentPrescriptionId = null;
                            }
                            if (modal === qtyModal) {
                                selectedProductLi = null;
                            }
                            if (modal === completeOrderModal) {
                                currentOrderPrescriptionId = null;
                            }
                        }
                    });
                }
            });

            // Initialize everything
            initializeTooltips();
            filterOrders();
            let currentChatPrescriptionId = null;

            // Chat modal elements
            const chatModal = document.getElementById('chatModal');
            const chatMessages = document.getElementById('chatMessages');
            const chatInput = document.getElementById('chatInput');
            const sendButton = document.getElementById('sendMessage');
            const chatOrderId = document.getElementById('chatOrderId');

            // Open chat modal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('chat-order-btn')) {
                    console.log('Chat button clicked!'); // Debug log
                    closeAllDropdowns();
                    const prescriptionId = e.target.dataset.id;
                    currentChatPrescriptionId = prescriptionId;

                    // Update chat title
                    const orderRow = e.target.closest('.order-row');
                    const orderIdElement = orderRow.querySelector('strong');
                    if (orderIdElement && chatOrderId) {
                        chatOrderId.textContent = `Chat - ${orderIdElement.textContent}`;
                    }

                    if (chatModal) {
                        chatModal.classList.add('active');
                        loadMessages(prescriptionId);
                        markMessagesAsRead(prescriptionId);
                    }
                }
            });

            // Close chat modal
            document.getElementById('closeChatModal')?.addEventListener('click', () => {
                if (chatModal) {
                    chatModal.classList.remove('active');
                }
                currentChatPrescriptionId = null;
            });

            // Close chat when clicking outside
            chatModal?.addEventListener('click', (e) => {
                if (e.target === chatModal) {
                    chatModal.classList.remove('active');
                    currentChatPrescriptionId = null;
                }
            });

            // Load messages
            async function loadMessages(prescriptionId) {
                if (!chatMessages) return;

                chatMessages.innerHTML = '<div class="no-messages">Loading messages...</div>';

                try {
                    const response = await fetch(`/admin/orders/${prescriptionId}/messages`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCSRFToken()
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        displayMessages(data.messages);
                    }
                } catch (error) {
                    console.error('Error loading messages:', error);
                    chatMessages.innerHTML = '<div class="no-messages">Error loading messages</div>';
                }
            }

            // Display messages
            function displayMessages(messages) {
                if (!chatMessages) return;

                if (messages.length === 0) {
                    chatMessages.innerHTML =
                        '<div class="no-messages">No messages yet. Start the conversation!</div>';
                    return;
                }

                chatMessages.innerHTML = '';

                messages.forEach(message => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${message.sender_type}`;

                    messageDiv.innerHTML = `
                        <div class="message-avatar">${message.sender_type === 'admin' ? 'A' : 'C'}</div>
                        <div class="message-content">
                            <div class="message-bubble">${message.message}</div>
                            <div class="message-time">${message.created_at}</div>
                        </div>
                    `;

                    chatMessages.appendChild(messageDiv);
                });

                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            // Send message
            async function sendMessage() {
                if (!currentChatPrescriptionId || !chatInput) return;

                const message = chatInput.value.trim();
                if (!message) return;

                const originalText = sendButton ? sendButton.innerHTML : '';
                if (sendButton) {
                    sendButton.disabled = true;
                    sendButton.innerHTML = '...';
                }

                try {
                    const response = await fetch(`/admin/orders/${currentChatPrescriptionId}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRFToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            message: message
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        chatInput.value = '';

                        // Add message to display
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'message admin';
                        messageDiv.innerHTML = `
                            <div class="message-avatar">A</div>
                            <div class="message-content">
                                <div class="message-bubble">${data.message.message}</div>
                                <div class="message-time">${data.message.created_at}</div>
                            </div>
                        `;

                        if (chatMessages.querySelector('.no-messages')) {
                            chatMessages.innerHTML = '';
                        }

                        chatMessages.appendChild(messageDiv);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                } finally {
                    if (sendButton) {
                        sendButton.disabled = false;
                        sendButton.innerHTML = originalText;
                    }
                }
            }

            // Send message on button click
            sendButton?.addEventListener('click', sendMessage);

            // Send message on Enter key (but allow Shift+Enter for new line)
            chatInput?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Auto-resize textarea
            chatInput?.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });

            // Mark messages as read
            async function markMessagesAsRead(prescriptionId) {
                try {
                    await fetch(`/admin/orders/${prescriptionId}/messages/mark-read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCSRFToken(),
                            'Accept': 'application/json'
                        }
                    });
                } catch (error) {
                    console.error('Error marking messages as read:', error);
                }
            }

            // Initialize everything
            initializeTooltips();
            filterOrders();
        });
    </script>
    @stack('scripts')
</body>

</html>
