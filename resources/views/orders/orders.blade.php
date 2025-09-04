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
                <div class="stat-label">Prescriptions Orders</div>
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
                                                <button class="btn-file btn-view"
                                                    onclick="viewPrescriptionInModal({{ $prescription->id }}, 'image', '{{ $prescription->original_filename ?? 'Prescription' }}', '{{ route('prescription.file.view', $prescription->id) }}', '{{ route('prescription.file.download', $prescription->id) }}')">
                                                    View Image
                                                </button>
                                            @elseif($prescription->file_mime_type === 'application/pdf')
                                                <button class="btn-file btn-view"
                                                    onclick="viewPrescriptionInModal({{ $prescription->id }}, 'pdf', '{{ $prescription->original_filename ?? 'Prescription' }}', '{{ route('prescription.file.view', $prescription->id) }}', '{{ route('prescription.file.download', $prescription->id) }}')">
                                                    View PDF
                                                </button>
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
                                            style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 70px; font-size: 0.75em; font-weight: bold;">UNENCRYPTED</span>
                                        <div class="file-actions">
                                            <button class="btn-file btn-view"
                                                onclick="viewPrescriptionInModal({{ $prescription->id }}, 'legacy', 'Legacy File', '{{ asset('storage/' . $prescription->file_path) }}', '{{ asset('storage/' . $prescription->file_path) }}')">
                                                View File
                                            </button>
                                        </div>
                                        <div style="font-size: 0.75em; color: #856404; margin-top: 2px;">Uploaded
                                            before encryption</div>
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
            <div class="modal-content manage-order-content">
                <div class="modal-header">
                    <div class="modal-title">
                        Manage Order Products - <span id="manageOrderId"></span>
                    </div>
                    <div class="modal-close" onclick="closeManageOrderModal()">&times;</div>
                </div>

                <div class="modal-body manage-order-body">
                    <!-- Prescription Viewer Section -->
                    <div class="prescription-reference-section">
                        <div class="prescription-reference-header">
                            <h4>Prescription Reference</h4>
                            <div class="prescription-toggle-controls">
                                <button id="togglePrescriptionBtn" class="btn btn-secondary">Show
                                    Prescription</button>
                                <button id="refreshPrescriptionBtn" class="btn btn-outline"
                                    style="display: none;">Refresh</button>
                            </div>
                        </div>

                        <div id="prescriptionReferenceContainer" class="prescription-reference-container"
                            style="display: none;">
                            <div class="prescription-loading-ref" id="prescriptionLoadingRef" style="display: none;">
                                <div class="loading-spinner-small"></div>
                                <span>Loading prescription...</span>
                            </div>

                            <div class="prescription-content-ref" id="prescriptionContentRef">
                                <div class="no-prescription-message">
                                    <p>No prescription document available</p>
                                </div>
                            </div>

                            <div class="prescription-error-ref" id="prescriptionErrorRef" style="display: none;">
                                <p>Error loading prescription. <button class="retry-btn"
                                        onclick="retryLoadPrescription()">Retry</button></p>
                            </div>
                        </div>
                    </div>

                    <!-- Product Management Section -->
                    <div class="product-management-section">
                        <input type="text" id="productSearch" placeholder="Search products..."
                            class="product-search-input" />

                        <div class="product-management-container">
                            <div class="available-products-section">
                                <h4>Available Products</h4>
                                <ul id="availableProducts" class="product-list">
                                    <!-- Products will be populated here -->
                                </ul>
                            </div>

                            <div class="selected-products-section">
                                <h4>Selected Products</h4>
                                <ul id="selectedProducts" class="product-list selected-list">
                                    <!-- Selected products will appear here -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
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

    <div id="prescriptionViewerModal" class="modal">
        <div class="modal-content prescription-viewer-content">
            <div class="modal-header">
                <div class="modal-title">
                    Prescription Viewer - <span id="prescriptionModalOrderId"></span>
                </div>
                <div class="modal-close" onclick="closePrescriptionViewer()">&times;</div>
            </div>
            <div class="modal-body prescription-viewer-body">
                <div class="prescription-loading" id="prescriptionLoading">
                    <div class="loading-spinner"></div>
                    <p>Loading prescription...</p>
                </div>
                <div class="prescription-content" id="prescriptionContent" style="display: none;">
                    <!-- Content will be loaded here -->
                </div>
                <div class="prescription-error" id="prescriptionError" style="display: none;">
                    <p>Error loading prescription. Please try again.</p>
                </div>
            </div>
            <div class="prescription-viewer-footer">
                <button class="btn btn-secondary" onclick="closePrescriptionViewer()">Close</button>
                <button class="btn btn-primary" id="downloadPrescriptionBtn">Download</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let currentPrescriptionId = null;
            const selectedProductsByOrder = {};
            let currentManageOrderData = {
                prescriptionId: null,
                hasDocument: false,
                documentType: null,
                documentUrl: null
            };

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

            // Product management functionality
            if (productSearchInput) {
                productSearchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('#availableProducts li').forEach(li => {
                        const productName = (li.dataset.name || '').toLowerCase();
                        const isSelected = selectedProductsByOrder[currentPrescriptionId]?.find(p =>
                            p.id === li.dataset.id);

                        // Only show/hide if not already hidden by selection
                        if (!isSelected) {
                            li.style.display = productName.includes(searchTerm) ? 'block' : 'none';
                        }
                    });
                });
            }

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('manage-order-btn')) {
                    closeAllDropdowns();
                    const prescriptionId = e.target.dataset.id;
                    currentPrescriptionId = prescriptionId;

                    // Get prescription document info from the row
                    const orderRow = e.target.closest('.order-row');
                    const documentCell = orderRow.querySelector('td:nth-child(4)');
                    const viewButton = documentCell ? documentCell.querySelector('.btn-view') : null;

                    if (viewButton && viewButton.onclick) {
                        const onclickStr = viewButton.getAttribute('onclick');
                        const match = onclickStr.match(
                            /viewPrescriptionInModal\((\d+),\s*'([^']+)',\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)/
                        );

                        if (match) {
                            currentManageOrderData = {
                                prescriptionId: prescriptionId,
                                hasDocument: true,
                                documentType: match[2],
                                documentUrl: match[4],
                                filename: match[3]
                            };
                        }
                    } else {
                        currentManageOrderData = {
                            prescriptionId: prescriptionId,
                            hasDocument: false,
                            documentType: null,
                            documentUrl: null
                        };
                    }

                    // Update modal title
                    const manageOrderId = document.getElementById('manageOrderId');
                    if (manageOrderId && orderRow) {
                        const orderIdElement = orderRow.querySelector('strong');
                        if (orderIdElement) {
                            manageOrderId.textContent = orderIdElement.textContent;
                        }
                    }

                    // Reset and show modal
                    resetPrescriptionViewer();
                    if (manageModal) {
                        manageModal.style.display = 'flex';

                        // Clear search and reset products display
                        const searchInput = document.getElementById('productSearch');
                        if (searchInput) {
                            searchInput.value = '';
                        }
                        loadAvailableProducts();

                        // Load saved products
                        loadSavedProducts(prescriptionId);
                    }
                }
            });


            function closeManageOrderModal() {
                console.log('Closing manage order modal');
                if (manageModal) {
                    manageModal.style.display = 'none';
                }
                resetPrescriptionViewer();
                currentManageOrderData = {
                    prescriptionId: null,
                    hasDocument: false,
                    documentType: null,
                    documentUrl: null
                };
                currentPrescriptionId = null;
            }

            // Make it globally accessible
            window.closeManageOrderModal = closeManageOrderModal;

            // Single event listener for cancel button
            const cancelManageBtn = document.getElementById('cancelManageOrder');
            if (cancelManageBtn) {
                // Remove any existing listeners
                cancelManageBtn.replaceWith(cancelManageBtn.cloneNode(true));
                const newCancelBtn = document.getElementById('cancelManageOrder');

                newCancelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeManageOrderModal();
                });
            }

            const modalCloseBtn = document.querySelector('#manageOrderModal .modal-close');
            if (modalCloseBtn) {
                modalCloseBtn.replaceWith(modalCloseBtn.cloneNode(true));
                const newCloseBtn = document.querySelector('#manageOrderModal .modal-close');

                newCloseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeManageOrderModal();
                });
            }


            function initializeAvailableProductsDisplay() {
                // Clear search input
                const searchInput = document.getElementById('productSearch');
                if (searchInput) {
                    searchInput.value = '';
                }

                // Show all available products
                document.querySelectorAll('#availableProducts li').forEach(li => {
                    li.style.display = 'block';
                });
            }

            function resetPrescriptionViewer() {
                const container = document.getElementById('prescriptionReferenceContainer');
                const toggleBtn = document.getElementById('togglePrescriptionBtn');
                const refreshBtn = document.getElementById('refreshPrescriptionBtn');
                const contentRef = document.getElementById('prescriptionContentRef');
                const loadingRef = document.getElementById('prescriptionLoadingRef');
                const errorRef = document.getElementById('prescriptionErrorRef');

                if (container) container.style.display = 'none';
                if (toggleBtn) toggleBtn.textContent = 'Show Prescription';
                if (refreshBtn) refreshBtn.style.display = 'none';
                if (contentRef) contentRef.innerHTML =
                    '<div class="no-prescription-message"><p>No prescription document available</p></div>';
                if (loadingRef) loadingRef.style.display = 'none';
                if (errorRef) errorRef.style.display = 'none';
            }

            // Toggle prescription viewer
            document.getElementById('togglePrescriptionBtn')?.addEventListener('click', function() {
                const container = document.getElementById('prescriptionReferenceContainer');
                const toggleBtn = document.getElementById('togglePrescriptionBtn');
                const refreshBtn = document.getElementById('refreshPrescriptionBtn');

                if (container.style.display === 'none') {
                    if (currentManageOrderData.hasDocument) {
                        container.style.display = 'block';
                        toggleBtn.textContent = 'Hide Prescription';
                        refreshBtn.style.display = 'inline-block';
                        loadPrescriptionReference();
                    } else {
                        container.style.display = 'block';
                        toggleBtn.textContent = 'Hide Prescription';
                    }
                } else {
                    container.style.display = 'none';
                    toggleBtn.textContent = 'Show Prescription';
                    refreshBtn.style.display = 'none';
                }
            });

            // Load prescription in reference viewer
            function loadPrescriptionReference() {
                if (!currentManageOrderData.hasDocument) {
                    return;
                }

                const loadingRef = document.getElementById('prescriptionLoadingRef');
                const contentRef = document.getElementById('prescriptionContentRef');
                const errorRef = document.getElementById('prescriptionErrorRef');

                if (loadingRef) loadingRef.style.display = 'flex';
                if (contentRef) contentRef.style.display = 'none';
                if (errorRef) errorRef.style.display = 'none';

                const {
                    documentType,
                    documentUrl
                } = currentManageOrderData;

                setTimeout(() => {
                    if (documentType === 'image' || documentType === 'legacy') {
                        const img = new Image();
                        img.onload = function() {
                            if (contentRef) {
                                contentRef.innerHTML =
                                    `<img src="${documentUrl}" alt="Prescription Document" />`;
                                contentRef.style.display = 'block';
                            }
                            if (loadingRef) loadingRef.style.display = 'none';
                        };
                        img.onerror = function() {
                            showPrescriptionReferenceError();
                        };
                        img.src = documentUrl;
                    } else if (documentType === 'pdf') {
                        if (contentRef) {
                            contentRef.innerHTML =
                                `<iframe src="${documentUrl}" title="Prescription PDF"></iframe>`;
                            contentRef.style.display = 'block';
                        }
                        if (loadingRef) loadingRef.style.display = 'none';
                    } else {
                        showPrescriptionReferenceError();
                    }
                }, 300);
            }

            // Refresh prescription
            document.getElementById('refreshPrescriptionBtn')?.addEventListener('click', function() {
                loadPrescriptionReference();
            });

            // Retry loading prescription
            window.retryLoadPrescription = function() {
                loadPrescriptionReference();
            };

            // Product management code
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

            // Helper functions for product batch display
            function formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            function getExpiryClass(expirationDate) {
                if (!expirationDate) return '';

                const expiry = new Date(expirationDate);
                const now = new Date();
                const daysUntilExpiry = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));

                if (daysUntilExpiry < 0) {
                    return 'expired';
                } else if (daysUntilExpiry <= 30) {
                    return 'expiring-soon';
                } else if (daysUntilExpiry <= 90) {
                    return 'expiring-moderate';
                } else {
                    return 'expiring-safe';
                }
            }

            function getStockClass(product) {
                const totalQuantity = getTotalQuantity(product);
                const reorderLevel = product.reorder_level || 10;

                if (totalQuantity === 0) {
                    return 'out-of-stock';
                } else if (totalQuantity <= reorderLevel) {
                    return 'low-stock';
                } else {
                    return 'in-stock';
                }
            }

            function getStockText(product) {
                const totalQuantity = getTotalQuantity(product);
                const activeBatches = getActiveBatches(product);

                if (totalQuantity === 0) {
                    return 'Out of Stock';
                } else {
                    return `${totalQuantity} available${activeBatches > 1 ? ` (${activeBatches} batches)` : ''}`;
                }
            }

            function getTotalQuantity(product) {
                if (product.batches && Array.isArray(product.batches)) {
                    return product.batches
                        .filter(batch => batch.quantity_remaining > 0)
                        .reduce((total, batch) => total + parseInt(batch.quantity_remaining), 0);
                }
                return product.stock || product.quantity || product.total_stock || 0;
            }

            function getActiveBatches(product) {
                if (product.batches && Array.isArray(product.batches)) {
                    return product.batches.filter(batch => batch.quantity_remaining > 0).length;
                }
                return 0;
            }

            function getProductPrice(product) {
                // Get price from the earliest expiring batch with stock
                if (product.batches && Array.isArray(product.batches)) {
                    const activeBatch = product.batches
                        .filter(batch => batch.quantity_remaining > 0)
                        .sort((a, b) => new Date(a.expiration_date) - new Date(b.expiration_date))[0];

                    if (activeBatch) {
                        return parseFloat(activeBatch.sale_price || 0).toFixed(2);
                    }
                }

                return parseFloat(product.price || product.sale_price || product.selling_price || 0).toFixed(2);
            }

            function getBatchInfo(product) {
                if (!product.batches || !Array.isArray(product.batches)) return '';

                const activeBatches = product.batches.filter(batch => batch.quantity_remaining > 0);
                if (activeBatches.length === 0) return '';

                const nextBatch = activeBatches.sort((a, b) => new Date(a.expiration_date) - new Date(b
                    .expiration_date))[0];

                return `<small class="next-batch">Next: ${nextBatch.batch_number} (${nextBatch.quantity_remaining} units)</small>`;
            }

            function getExpirationInfo(product) {
                if (!product.earliest_expiration && (!product.batches || !Array.isArray(product.batches)))
                    return '';

                let earliestExpiry = product.earliest_expiration;

                // If we don't have earliest_expiration from the controller, calculate it
                if (!earliestExpiry && product.batches && Array.isArray(product.batches)) {
                    const activeBatches = product.batches.filter(batch => batch.quantity_remaining > 0);
                    if (activeBatches.length > 0) {
                        earliestExpiry = activeBatches
                            .map(batch => batch.expiration_date)
                            .sort((a, b) => new Date(a) - new Date(b))[0];
                    }
                }

                if (!earliestExpiry) return '';

                const expiryClass = getExpiryClass(earliestExpiry);
                const formattedDate = formatDate(earliestExpiry);

                return `<small class="expiry-date ${expiryClass}">
        Expires: ${formattedDate}
    </small>`;
            }

            function loadAvailableProducts() {
                const availableList = document.getElementById('availableProducts');
                if (!availableList) {
                    console.error('Available products list not found');
                    return;
                }

                availableList.innerHTML = '<li style="text-align: center; padding: 20px;">Loading products...</li>';

                const csrfToken = getCSRFToken();

                // Try the admin products route first, then fallback to dashboard
                fetch('/admin/products', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        console.log('Products response status:', response.status);
                        console.log('Response headers:', response.headers.get('content-type'));

                        // Check if response is HTML (error page)
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('text/html')) {
                            throw new Error(
                                'Server returned HTML instead of JSON - check route and authentication');
                        }

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .catch(error => {
                        console.log('Admin route failed, trying dashboard route:', error.message);
                        // Fallback to dashboard route
                        return fetch('/dashboard/products', {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => {
                                console.log('Dashboard response status:', response.status);
                                console.log('Dashboard response headers:', response.headers.get(
                                    'content-type'));

                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('text/html')) {
                                    throw new Error(
                                        'Dashboard route also returned HTML - check Laravel routes');
                                }

                                if (!response.ok) {
                                    throw new Error(
                                        `Dashboard route HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            });
                    })
                    .then(data => {
                        console.log('Products data received:', data);

                        availableList.innerHTML = '';

                        // Handle different possible response formats
                        let products = [];
                        if (data.success && data.products) {
                            products = data.products;
                        } else if (data.data && Array.isArray(data.data)) {
                            products = data.data;
                        } else if (Array.isArray(data)) {
                            products = data;
                        } else if (data.products && Array.isArray(data.products)) {
                            products = data.products;
                        }

                        console.log('Processed products:', products);

                        if (products && products.length > 0) {
                            products.forEach(product => {
                                const li = document.createElement('li');
                                li.dataset.id = product.id;
                                li.dataset.product = product.name || product.product_name;
                                li.dataset.name = (product.name || product.product_name || '')
                                    .toLowerCase();
                                li.dataset.price = getProductPrice(product);

                                // Use helper function for stock quantity
                                const totalQuantity = getTotalQuantity(product);
                                li.dataset.stock = totalQuantity;

                                const stockClass = getStockClass(product);
                                li.className = stockClass;

                                li.innerHTML =
                                    '<span class="product-name">' + (product.product_name || product
                                        .name) + '</span>' +
                                    '<span class="product-details">' +
                                    (product.form_type ? '<span class="product-form">' + product
                                        .form_type + '</span>' : '') +
                                    (product.dosage_unit ? '<span class="product-dosage">' + product
                                        .dosage_unit + '</span>' : '') +
                                    ((product.form_type || product.dosage_unit) ?
                                        '<span class="separator">•</span>' : '') +
                                    (product.manufacturer ? '<span class="manufacturer">' + product
                                        .manufacturer + '</span>' : '') +
                                    '</span>' +
                                    '<span class="product-price">₱' + getProductPrice(product) +
                                    '</span>' +
                                    '<span class="product-stock ' + getStockClass(product) + '">' +
                                    getStockText(product) + '</span>' +
                                    '<span class="batch-info">' + getBatchInfo(product) + '</span>' +
                                    '<span class="expiration-info">' + getExpirationInfo(product) +
                                    '</span>';

                                availableList.appendChild(li);
                            });
                        } else {
                            availableList.innerHTML =
                                '<li style="text-align: center; padding: 20px; color: #666;">No products available</li>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading products:', error);
                        availableList.innerHTML = `
                <li style="text-align: center; padding: 20px; color: #d32f2f;">
                    Error loading products: ${error.message}
                    <br><button onclick="loadAvailableProducts()" style="margin-top: 10px; padding: 5px 10px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">Retry</button>
                </li>
            `;
                    });
            }

            function updateSelectedProductsDisplay() {
                console.log('updateSelectedProductsDisplay called');

                if (!selectedList) {
                    console.error('selectedList element not found');
                    return;
                }

                if (!currentPrescriptionId) {
                    console.error('currentPrescriptionId is null');
                    return;
                }

                selectedList.innerHTML = '';
                const selectedProducts = selectedProductsByOrder[currentPrescriptionId] || [];
                console.log('Updating display for products:', selectedProducts);

                // Clear search input
                const searchInput = document.getElementById('productSearch');
                if (searchInput) {
                    searchInput.value = '';
                }

                // Show all available products first
                const availableProducts = document.querySelectorAll('#availableProducts li');
                availableProducts.forEach(li => {
                    li.style.display = 'block';
                });

                // Process selected products
                selectedProducts.forEach((product, index) => {
                    console.log(`Processing selected product ${index + 1}:`, product);

                    // Hide from available list - handle both string and number IDs
                    const availableLi = document.querySelector(
                        `#availableProducts li[data-id="${product.id}"]`);
                    if (availableLi) {
                        availableLi.style.display = 'none';
                    } else {
                        console.warn('Available product not found for ID:', product.id);
                    }

                    // Create selected product item
                    const li = document.createElement('li');
                    li.dataset.id = product.id.toString(); // Ensure it's always a string
                    li.style.cssText =
                        'display: flex; justify-content: space-between; align-items: center; padding: 8px; margin: 4px 0; background: #f8f9fa; border-radius: 4px;';

                    li.innerHTML = `
            <span style="flex: 1;">${product.name} — Qty: ${product.quantity}</span>
            <button class="remove-btn"
                    data-id="${product.id}"
                    type="button"
                    style="margin-left: 10px; padding: 4px 8px; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; min-width: 24px; height: 24px;">
                ❌
            </button>
        `;

                    selectedList.appendChild(li);
                });

                console.log('Display update completed. Selected products shown:', selectedProducts.length);
            }

            if (selectedList) {
                selectedList.addEventListener('click', e => {
                    console.log('Clicked element:', e.target);

                    if (e.target.classList.contains('remove-btn')) {
                        e.preventDefault();
                        e.stopPropagation();

                        const productId = e.target.dataset.id;
                        console.log('Removing product with ID (string):', productId);
                        console.log('Current prescription ID:', currentPrescriptionId);
                        console.log('Selected products before removal:', selectedProductsByOrder[
                            currentPrescriptionId]);

                        // Log the data types for debugging
                        if (selectedProductsByOrder[currentPrescriptionId]) {
                            console.log('Existing product IDs and types:');
                            selectedProductsByOrder[currentPrescriptionId].forEach((product, index) => {
                                console.log(
                                    `Product ${index}: ID = ${product.id} (type: ${typeof product.id})`
                                );
                            });
                            console.log('Button product ID type:', typeof productId);
                        }

                        if (selectedProductsByOrder[currentPrescriptionId]) {
                            const originalLength = selectedProductsByOrder[currentPrescriptionId].length;

                            // Fix: Compare both string and number versions
                            selectedProductsByOrder[currentPrescriptionId] = selectedProductsByOrder[
                                    currentPrescriptionId]
                                .filter(p => {
                                    // Convert both to strings for comparison OR both to numbers
                                    const productIdMatch = (p.id.toString() === productId.toString()) ||
                                        (parseInt(p.id) === parseInt(productId));
                                    console.log(
                                        `Comparing product ${p.id} with ${productId}: match = ${productIdMatch}`
                                    );
                                    return !productIdMatch; // Keep products that DON'T match
                                });

                            console.log('Products count - before:', originalLength, 'after:',
                                selectedProductsByOrder[currentPrescriptionId].length);
                            console.log('Updated selected products:', selectedProductsByOrder[
                                currentPrescriptionId]);

                            try {
                                updateSelectedProductsDisplay();
                                console.log('Display updated successfully');
                            } catch (error) {
                                console.error('Error updating display:', error);
                            }
                        } else {
                            console.error('No selected products found for prescription:',
                                currentPrescriptionId);
                        }
                    }
                });
            }

            function loadSavedProducts(prescriptionId) {
                console.log('Loading saved products for prescription:', prescriptionId);

                // Initialize empty array if not exists
                if (!selectedProductsByOrder[prescriptionId]) {
                    selectedProductsByOrder[prescriptionId] = [];
                }

                // Try to load saved products from server
                fetch(`/prescriptions/${prescriptionId}/items`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCSRFToken()
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        }
                        throw new Error('No saved products found');
                    })
                    .then(data => {
                        console.log('Saved products loaded:', data);
                        if (data.success && data.items) {
                            selectedProductsByOrder[prescriptionId] = data.items.map(item => ({
                                id: item.product_id,
                                name: item.product_name || item.name,
                                price: item.unit_price || item.price,
                                quantity: item.quantity
                            }));
                        }
                        updateSelectedProductsDisplay();
                    })
                    .catch(error => {
                        console.log('No saved products or error:', error.message);
                        selectedProductsByOrder[prescriptionId] = [];
                        updateSelectedProductsDisplay();
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


            // Prescription viewer functionality
            let currentPrescriptionData = {
                id: null,
                type: null,
                filename: null,
                viewUrl: null,
                downloadUrl: null
            };

            // Make these functions global so they can be called from onclick
            window.viewPrescriptionInModal = function(id, type, filename, viewUrl, downloadUrl) {
                console.log('viewPrescriptionInModal called with:', {
                    id,
                    type,
                    filename,
                    viewUrl,
                    downloadUrl
                });

                currentPrescriptionData = {
                    id,
                    type,
                    filename,
                    viewUrl,
                    downloadUrl
                };

                const modal = document.getElementById('prescriptionViewerModal');
                const orderIdSpan = document.getElementById('prescriptionModalOrderId');
                const loadingDiv = document.getElementById('prescriptionLoading');
                const contentDiv = document.getElementById('prescriptionContent');
                const errorDiv = document.getElementById('prescriptionError');

                console.log('Modal elements found:', {
                    modal: !!modal,
                    orderIdSpan: !!orderIdSpan,
                    loadingDiv: !!loadingDiv,
                    contentDiv: !!contentDiv,
                    errorDiv: !!errorDiv
                });

                if (!modal) {
                    console.error('Modal element not found!');
                    alert('Modal not found. Please check if the modal HTML was added correctly.');
                    return;
                }

                if (orderIdSpan) {
                    orderIdSpan.textContent = filename;
                }

                modal.style.display = 'flex';
                modal.classList.add('active');
                console.log('Modal should be visible now');

                if (loadingDiv) loadingDiv.style.display = 'flex';
                if (contentDiv) contentDiv.style.display = 'none';
                if (errorDiv) errorDiv.style.display = 'none';

                setTimeout(() => {
                    try {
                        loadPrescriptionContent(type, viewUrl);
                    } catch (error) {
                        console.error('Error loading prescription:', error);
                        showPrescriptionError();
                    }
                }, 500);
            };

            window.closePrescriptionViewer = function() {
                console.log('Closing prescription viewer');
                const modal = document.getElementById('prescriptionViewerModal');
                const contentDiv = document.getElementById('prescriptionContent');

                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                }

                if (contentDiv) {
                    contentDiv.innerHTML = '';
                }

                currentPrescriptionData = {
                    id: null,
                    type: null,
                    filename: null,
                    viewUrl: null,
                    downloadUrl: null
                };
            };

            function loadPrescriptionContent(type, viewUrl) {
                console.log('Loading prescription content:', {
                    type,
                    viewUrl
                });

                const loadingDiv = document.getElementById('prescriptionLoading');
                const contentDiv = document.getElementById('prescriptionContent');
                const errorDiv = document.getElementById('prescriptionError');

                if (type === 'image' || type === 'legacy') {
                    console.log('Loading as image...');
                    const img = new Image();
                    img.onload = function() {
                        console.log('Image loaded successfully');
                        if (contentDiv) {
                            contentDiv.innerHTML =
                                `<img src="${viewUrl}" alt="Prescription Document" style="max-width: 100%; max-height: 100%; object-fit: contain;" />`;
                            contentDiv.style.display = 'flex';
                        }
                        if (loadingDiv) loadingDiv.style.display = 'none';
                    };
                    img.onerror = function() {
                        console.error('Image failed to load');
                        showPrescriptionError();
                    };
                    img.src = viewUrl;

                } else if (type === 'pdf') {
                    console.log('Loading as PDF...');
                    if (contentDiv) {
                        contentDiv.innerHTML =
                            `<iframe src="${viewUrl}" title="Prescription PDF" style="width: 100%; height: 600px; border: none;"></iframe>`;
                        contentDiv.style.display = 'flex';
                    }
                    if (loadingDiv) loadingDiv.style.display = 'none';

                } else {
                    console.error('Unknown file type:', type);
                    showPrescriptionError();
                }
            }

            function showPrescriptionError() {
                console.log('Showing error state');
                const loadingDiv = document.getElementById('prescriptionLoading');
                const contentDiv = document.getElementById('prescriptionContent');
                const errorDiv = document.getElementById('prescriptionError');

                if (loadingDiv) loadingDiv.style.display = 'none';
                if (contentDiv) contentDiv.style.display = 'none';
                if (errorDiv) errorDiv.style.display = 'flex';
            }

            // Download button handler
            const downloadBtn = document.getElementById('downloadPrescriptionBtn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    console.log('Download button clicked', currentPrescriptionData.downloadUrl);
                    if (currentPrescriptionData.downloadUrl) {
                        window.open(currentPrescriptionData.downloadUrl, '_blank');
                    }
                });
            }

            // Close modal when clicking outside
            const prescriptionModal = document.getElementById('prescriptionViewerModal');
            if (prescriptionModal) {
                prescriptionModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        window.closePrescriptionViewer();
                    }
                });
            }

            // Handle escape key for prescription modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('prescriptionViewerModal')?.classList
                    .contains('active')) {
                    window.closePrescriptionViewer();
                }
            });
        });
    </script>
    @stack('scripts')
</body>

</html>
