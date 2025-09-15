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
                                    <span class="status-badges{{ strtolower($prescription->status ?? 'pending') }}">
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
                                            data-id="{{ $prescription->id }}">Process Order</button>
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
        /**
         * Enhanced Order Management System
         * Organized and optimized JavaScript for pharmacy order management
         */

        class OrderManagementSystem {
            constructor() {
                this.state = {
                    currentPrescriptionId: null,
                    currentOrderPrescriptionId: null,
                    currentChatPrescriptionId: null,
                    selectedProductLi: null,
                    selectedProductsByOrder: {},
                    currentManageOrderData: {
                        prescriptionId: null,
                        hasDocument: false,
                        documentType: null,
                        documentUrl: null
                    },
                    currentPrescriptionData: {
                        id: null,
                        type: null,
                        filename: null,
                        viewUrl: null,
                        downloadUrl: null
                    }
                };

                this.elements = {};
                this.statusMessages = {
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
                    }
                };

                this.init();
            }

            init() {
                this.cacheElements();
                this.bindEvents();
                this.initializeTooltips();
                this.filterOrders();
            }

            cacheElements() {
                // Modal elements
                this.elements.dropdownOverlay = document.querySelector('.dropdown-overlay');
                this.elements.statusModal = document.getElementById('manageStatusModal');
                this.elements.manageModal = document.getElementById('manageOrderModal');
                this.elements.qtyModal = document.getElementById('productQuantityModal');
                this.elements.completeOrderModal = document.getElementById('completeOrderModal');
                this.elements.chatModal = document.getElementById('chatModal');
                this.elements.prescriptionModal = document.getElementById('prescriptionViewerModal');

                // Form elements
                this.elements.statusForm = document.getElementById('statusForm');
                this.elements.statusPrescriptionId = document.getElementById('statusPrescriptionId');
                this.elements.statusSelect = document.getElementById('statusSelect');
                this.elements.reasonMessage = document.getElementById('reasonMessage');

                // Product management elements
                this.elements.availableList = document.getElementById('availableProducts');
                this.elements.selectedList = document.getElementById('selectedProducts');
                this.elements.productSearchInput = document.getElementById('productSearch');
                this.elements.qtyInput = document.getElementById('productQty');
                this.elements.productModalName = document.getElementById('productModalName');

                // Order completion elements
                this.elements.orderSummary = document.getElementById('orderSummary');
                this.elements.submitOrderBtn = document.getElementById('submitOrderBtn');

                // Search and filter elements
                this.elements.searchInput = document.getElementById('order-search');
                this.elements.statusFilter = document.getElementById('status-filter');
                this.elements.typeFilters = document.querySelectorAll('.filter-btn[data-filter]');

                // Chat elements
                this.elements.chatMessages = document.getElementById('chatMessages');
                this.elements.chatInput = document.getElementById('chatInput');
                this.elements.sendButton = document.getElementById('sendMessage');
                this.elements.chatOrderId = document.getElementById('chatOrderId');
            }

            bindEvents() {
                this.bindGlobalEvents();
                this.bindSearchAndFilter();
                this.bindDropdownEvents();
                this.bindModalEvents();
                this.bindStatusManagement();
                this.bindProductManagement();
                this.bindOrderCompletion();
                this.bindChatFunctionality();
                this.bindPrescriptionViewer();
            }

            bindGlobalEvents() {
                document.addEventListener('keydown', (e) => {
                    if (e.key === "Escape") {
                        this.closeAllDropdowns();
                        this.closeAllModals();
                    }
                });
            }

            bindSearchAndFilter() {
                if (this.elements.searchInput) {
                    this.elements.searchInput.addEventListener('input', () => this.filterOrders());
                }

                if (this.elements.statusFilter) {
                    this.elements.statusFilter.addEventListener('change', () => this.filterOrders());
                }

                this.elements.typeFilters.forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.elements.typeFilters.forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        this.filterOrders();
                    });
                });
            }

            bindDropdownEvents() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('dropdown-trigger')) {
                        e.stopPropagation();
                        this.handleDropdownTrigger(e.target);
                    } else if (!e.target.closest('.action-dropdown')) {
                        this.closeAllDropdowns();
                    }
                });

                if (this.elements.dropdownOverlay) {
                    this.elements.dropdownOverlay.addEventListener('click', () => this.closeAllDropdowns());
                }
            }

            bindModalEvents() {
                // Close modals when clicking outside
                [this.elements.statusModal, this.elements.manageModal, this.elements.qtyModal,
                    this.elements.completeOrderModal, this.elements.chatModal, this.elements.prescriptionModal
                ]
                .forEach(modal => {
                    if (modal) {
                        modal.addEventListener('click', (e) => {
                            if (e.target === e.currentTarget) {
                                this.closeModal(modal);
                            }
                        });
                    }
                });

                // Cancel buttons
                document.getElementById('cancelStatusModal')?.addEventListener('click', () => this.closeStatusModal());
                document.getElementById('cancelManageOrder')?.addEventListener('click', () => this
                    .closeManageOrderModal());
                document.getElementById('cancelQtyModal')?.addEventListener('click', () => this.closeQtyModal());
                document.getElementById('cancelCompleteModal')?.addEventListener('click', () => this
                    .closeCompleteOrderModal());
                document.getElementById('closeChatModal')?.addEventListener('click', () => this.closeChatModal());

                // Close buttons
                document.querySelector('#manageOrderModal .modal-close')?.addEventListener('click', () => this
                    .closeManageOrderModal());
            }

            bindStatusManagement() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('manage-status-btn')) {
                        this.openStatusModal(e.target.dataset.id);
                    }
                });

                if (this.elements.statusForm) {
                    this.elements.statusForm.addEventListener('submit', (e) => this.handleStatusSubmit(e));
                }
            }

            bindProductManagement() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('manage-order-btn')) {
                        this.openManageOrderModal(e.target.dataset.id, e.target);
                    }
                });

                if (this.elements.productSearchInput) {
                    this.elements.productSearchInput.addEventListener('input', () => this.handleProductSearch());
                }

                if (this.elements.availableList) {
                    this.elements.availableList.addEventListener('click', (e) => this.handleAvailableProductClick(e));
                }

                if (this.elements.selectedList) {
                    this.elements.selectedList.addEventListener('click', (e) => this.handleSelectedProductClick(e));
                }

                // Quantity modal events
                document.getElementById('increaseQty')?.addEventListener('click', () => this.increaseQuantity());
                document.getElementById('decreaseQty')?.addEventListener('click', () => this.decreaseQuantity());
                document.getElementById('confirmQtyModal')?.addEventListener('click', () => this
                    .confirmQuantitySelection());

                if (this.elements.qtyInput) {
                    this.elements.qtyInput.addEventListener('input', () => this.validateQuantityInput());
                }

                // Save selection
                document.getElementById('saveSelection')?.addEventListener('click', (e) => this.saveProductSelection(
                e));

                // Prescription viewer toggle
                document.getElementById('togglePrescriptionBtn')?.addEventListener('click', () => this
                    .togglePrescriptionViewer());
                document.getElementById('refreshPrescriptionBtn')?.addEventListener('click', () => this
                    .loadPrescriptionReference());
            }

            bindOrderCompletion() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('complete-order-btn')) {
                        this.openCompleteOrderModal(e.target.dataset.id);
                    }
                });

                if (this.elements.submitOrderBtn) {
                    this.elements.submitOrderBtn.addEventListener('click', (e) => this.submitOrderCompletion(e));
                }
            }

            bindChatFunctionality() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('chat-order-btn')) {
                        this.openChatModal(e.target.dataset.id, e.target);
                    }
                });

                this.elements.sendButton?.addEventListener('click', () => this.sendMessage());

                if (this.elements.chatInput) {
                    this.elements.chatInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            this.sendMessage();
                        }
                    });

                    // Auto-resize textarea
                    this.elements.chatInput.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
                    });
                }
            }

            bindPrescriptionViewer() {
                // Global functions for onclick handlers
                window.viewPrescriptionInModal = (id, type, filename, viewUrl, downloadUrl) => {
                    this.viewPrescriptionInModal(id, type, filename, viewUrl, downloadUrl);
                };

                window.closePrescriptionViewer = () => this.closePrescriptionViewer();
                window.retryLoadPrescription = () => this.loadPrescriptionReference();
                window.closeManageOrderModal = () => this.closeManageOrderModal();

                // Download button
                document.getElementById('downloadPrescriptionBtn')?.addEventListener('click', () => {
                    if (this.state.currentPrescriptionData.downloadUrl) {
                        window.open(this.state.currentPrescriptionData.downloadUrl, '_blank');
                    }
                });
            }

            // Utility Methods
            getCSRFToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            }

            closeAllDropdowns() {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                if (this.elements.dropdownOverlay) {
                    this.elements.dropdownOverlay.style.display = 'none';
                }
            }

            closeAllModals() {
                [this.elements.statusModal, this.elements.manageModal, this.elements.qtyModal,
                    this.elements.completeOrderModal, this.elements.chatModal, this.elements.prescriptionModal
                ]
                .forEach(modal => {
                    if (modal) {
                        this.closeModal(modal);
                    }
                });
                this.resetState();
            }

            closeModal(modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }

            resetState() {
                this.state.currentPrescriptionId = null;
                this.state.currentOrderPrescriptionId = null;
                this.state.currentChatPrescriptionId = null;
                this.state.selectedProductLi = null;
            }

            // Tooltip Management
            initializeTooltips() {
                const statusIcons = document.querySelectorAll('.status-info-icon');

                statusIcons.forEach(icon => {
                    const status = icon.dataset.status;
                    const orderType = icon.dataset.orderType;
                    const tooltip = icon.querySelector('.status-tooltip');

                    if (tooltip) {
                        const message = this.statusMessages[status]?.[orderType] ||
                            this.statusMessages[status]?.['prescription'] ||
                            'Status information not available.';
                        tooltip.textContent = message;
                    }
                });

                statusIcons.forEach(icon => {
                    icon.addEventListener('click', (e) => this.handleTooltipClick(e, icon, statusIcons));
                });

                document.addEventListener('click', () => {
                    statusIcons.forEach(icon => {
                        const tooltip = icon.querySelector('.status-tooltip');
                        if (tooltip) {
                            tooltip.style.opacity = '0';
                            tooltip.style.visibility = 'hidden';
                        }
                    });
                });
            }

            handleTooltipClick(e, icon, allIcons) {
                e.stopPropagation();

                // Hide other tooltips
                allIcons.forEach(otherIcon => {
                    if (otherIcon !== icon) {
                        const otherTooltip = otherIcon.querySelector('.status-tooltip');
                        if (otherTooltip) {
                            otherTooltip.style.opacity = '0';
                            otherTooltip.style.visibility = 'hidden';
                        }
                    }
                });

                // Toggle current tooltip
                const tooltip = icon.querySelector('.status-tooltip');
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
            }

            // Filter and Search
            filterOrders() {
                const searchTerm = this.elements.searchInput ? this.elements.searchInput.value.toLowerCase() : '';
                const statusFilterValue = this.elements.statusFilter ? this.elements.statusFilter.value : 'all';
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

            // Dropdown Management
            handleDropdownTrigger(trigger) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });

                const dropdown = trigger.nextElementSibling;
                if (dropdown) {
                    dropdown.classList.toggle('show');
                    if (dropdown.classList.contains('show') && this.elements.dropdownOverlay) {
                        this.elements.dropdownOverlay.style.display = 'block';
                    }
                }
            }

            // Status Management
            openStatusModal(id) {
                this.closeAllDropdowns();
                if (this.elements.statusPrescriptionId) this.elements.statusPrescriptionId.value = id;
                if (this.elements.statusSelect) this.elements.statusSelect.selectedIndex = 0;
                if (this.elements.reasonMessage) this.elements.reasonMessage.value = '';
                if (this.elements.statusModal) this.elements.statusModal.style.display = 'flex';
            }

            closeStatusModal() {
                if (this.elements.statusModal) this.elements.statusModal.style.display = 'none';
                if (this.elements.statusSelect) this.elements.statusSelect.selectedIndex = 0;
                if (this.elements.reasonMessage) this.elements.reasonMessage.value = '';
            }

            async handleStatusSubmit(e) {
                e.preventDefault();

                const id = this.elements.statusPrescriptionId ? this.elements.statusPrescriptionId.value : '';
                const action = this.elements.statusSelect ? this.elements.statusSelect.value : '';
                const message = this.elements.reasonMessage ? this.elements.reasonMessage.value.trim() : '';

                if (!action) {
                    alert('Please select a status.');
                    return;
                }

                if (!message) {
                    alert('Please enter a reason or message.');
                    return;
                }

                const submitBtn = this.elements.statusForm.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : 'Submit';
                if (submitBtn) {
                    submitBtn.textContent = 'Processing...';
                    submitBtn.disabled = true;
                }

                try {
                    const formData = new FormData();
                    formData.append('_token', this.getCSRFToken());
                    formData.append('message', message);

                    const response = await fetch(`/admin/orders/${id}/${action}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': this.getCSRFToken(),
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Server error:', text);
                        throw new Error(`Server error: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message || 'Action completed successfully!');
                        this.closeStatusModal();
                        window.location.reload();
                    } else {
                        alert(data.message || 'Action failed!');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred: ' + error.message);
                } finally {
                    if (submitBtn) {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }
                }
            }

            // Product Management
            openManageOrderModal(prescriptionId, triggerElement) {
                this.closeAllDropdowns();
                this.state.currentPrescriptionId = prescriptionId;

                // Get prescription document info
                const orderRow = triggerElement.closest('.order-row');
                const documentCell = orderRow.querySelector('td:nth-child(4)');
                const viewButton = documentCell ? documentCell.querySelector('.btn-view') : null;

                if (viewButton && viewButton.onclick) {
                    const onclickStr = viewButton.getAttribute('onclick');
                    const match = onclickStr.match(
                        /viewPrescriptionInModal\((\d+),\s*'([^']+)',\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)/
                    );

                    if (match) {
                        this.state.currentManageOrderData = {
                            prescriptionId: prescriptionId,
                            hasDocument: true,
                            documentType: match[2],
                            documentUrl: match[4],
                            filename: match[3]
                        };
                    }
                } else {
                    this.state.currentManageOrderData = {
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
                this.resetPrescriptionViewer();
                if (this.elements.manageModal) {
                    this.elements.manageModal.style.display = 'flex';

                    // Clear search and reset products display
                    if (this.elements.productSearchInput) {
                        this.elements.productSearchInput.value = '';
                    }
                    this.loadAvailableProducts();
                    this.loadSavedProducts(prescriptionId);
                }
            }

            closeManageOrderModal() {
                if (this.elements.manageModal) {
                    this.elements.manageModal.style.display = 'none';
                }
                this.resetPrescriptionViewer();
                this.state.currentManageOrderData = {
                    prescriptionId: null,
                    hasDocument: false,
                    documentType: null,
                    documentUrl: null
                };
                this.state.currentPrescriptionId = null;
            }

            handleProductSearch() {
                const searchTerm = this.elements.productSearchInput.value.toLowerCase();
                document.querySelectorAll('#availableProducts li').forEach(li => {
                    const productName = (li.dataset.name || '').toLowerCase();
                    const isSelected = this.state.selectedProductsByOrder[this.state.currentPrescriptionId]
                        ?.find(p =>
                            p.id === li.dataset.id);

                    if (!isSelected) {
                        li.style.display = productName.includes(searchTerm) ? 'block' : 'none';
                    }
                });
            }

            handleAvailableProductClick(e) {
                if (e.target.tagName === 'LI') {
                    const stockLevel = parseInt(e.target.dataset.stock) || 0;
                    if (stockLevel <= 0) {
                        alert('This product is out of stock.');
                        return;
                    }

                    this.state.selectedProductLi = e.target;
                    if (this.elements.qtyInput) {
                        this.elements.qtyInput.value = 1;
                        this.elements.qtyInput.max = stockLevel;
                    }
                    if (this.elements.productModalName) {
                        this.elements.productModalName.textContent = this.state.selectedProductLi.dataset.product || '';
                    }
                    if (this.elements.qtyModal) this.elements.qtyModal.style.display = 'flex';
                }
            }

            handleSelectedProductClick(e) {
                if (e.target.classList.contains('remove-btn')) {
                    e.preventDefault();
                    e.stopPropagation();

                    const productId = e.target.dataset.id;

                    if (this.state.selectedProductsByOrder[this.state.currentPrescriptionId]) {
                        this.state.selectedProductsByOrder[this.state.currentPrescriptionId] =
                            this.state.selectedProductsByOrder[this.state.currentPrescriptionId]
                            .filter(p => p.id.toString() !== productId.toString());

                        this.updateSelectedProductsDisplay();
                    }
                }
            }

            closeQtyModal() {
                if (this.elements.qtyModal) this.elements.qtyModal.style.display = 'none';
                this.state.selectedProductLi = null;
            }

            increaseQuantity() {
                if (this.state.selectedProductLi && this.elements.qtyInput) {
                    const currentQty = parseInt(this.elements.qtyInput.value) || 1;
                    const maxStock = parseInt(this.state.selectedProductLi.dataset.stock) || 0;
                    if (currentQty < maxStock) {
                        this.elements.qtyInput.value = currentQty + 1;
                    } else {
                        alert(`Maximum stock available: ${maxStock}`);
                    }
                }
            }

            decreaseQuantity() {
                if (this.elements.qtyInput && this.elements.qtyInput.value > 1) {
                    this.elements.qtyInput.value = parseInt(this.elements.qtyInput.value) - 1;
                }
            }

            validateQuantityInput() {
                const currentQty = parseInt(this.elements.qtyInput.value) || 1;
                const maxStock = parseInt(this.state.selectedProductLi?.dataset.stock || 999);

                if (currentQty < 1) {
                    this.elements.qtyInput.value = 1;
                } else if (currentQty > maxStock) {
                    this.elements.qtyInput.value = maxStock;
                    alert(`Maximum stock available: ${maxStock}`);
                }
            }

            confirmQuantitySelection() {
                if (!this.state.selectedProductLi || !this.elements.qtyInput) return;

                const quantity = parseInt(this.elements.qtyInput.value) || 1;
                const id = this.state.selectedProductLi.dataset.id;
                const name = this.state.selectedProductLi.dataset.product;
                const price = this.state.selectedProductLi.dataset.price;
                const maxStock = parseInt(this.state.selectedProductLi.dataset.stock) || 0;

                if (quantity > maxStock) {
                    alert(`Cannot select ${quantity} items. Only ${maxStock} available in stock.`);
                    return;
                }

                if (!this.state.selectedProductsByOrder[this.state.currentPrescriptionId]) {
                    this.state.selectedProductsByOrder[this.state.currentPrescriptionId] = [];
                }

                const exists = this.state.selectedProductsByOrder[this.state.currentPrescriptionId].find(p => p.id ===
                    id);
                if (!exists) {
                    this.state.selectedProductsByOrder[this.state.currentPrescriptionId].push({
                        id: id,
                        name: name,
                        price: price,
                        quantity: quantity
                    });
                    this.updateSelectedProductsDisplay();
                } else {
                    alert('Product already selected!');
                }

                this.closeQtyModal();
            }

            async loadAvailableProducts() {
                const availableList = this.elements.availableList;
                if (!availableList) {
                    console.error('Available products list not found');
                    return;
                }

                availableList.innerHTML = '<li style="text-align: center; padding: 20px;">Loading products...</li>';

                try {
                    const response = await this.fetchWithFallback('/admin/products', '/dashboard/products');
                    const data = await response.json();

                    let products = this.extractProductsFromResponse(data);

                    availableList.innerHTML = '';

                    if (products && products.length > 0) {
                        products.forEach(product => {
                            const li = this.createProductListItem(product);
                            availableList.appendChild(li);
                        });
                    } else {
                        availableList.innerHTML =
                            '<li style="text-align: center; padding: 20px; color: #666;">No products available</li>';
                    }
                } catch (error) {
                    console.error('Error loading products:', error);
                    availableList.innerHTML = `
                <li style="text-align: center; padding: 20px; color: #d32f2f;">
                    Error loading products: ${error.message}
                    <br><button onclick="orderManager.loadAvailableProducts()" style="margin-top: 10px; padding: 5px 10px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">Retry</button>
                </li>
            `;
                }
            }

            async fetchWithFallback(primaryUrl, fallbackUrl) {
                const csrfToken = this.getCSRFToken();
                const headers = {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                };

                try {
                    const response = await fetch(primaryUrl, {
                        method: 'GET',
                        headers
                    });

                    if (!response.ok || response.headers.get('content-type')?.includes('text/html')) {
                        throw new Error('Primary route failed');
                    }

                    return response;
                } catch (error) {
                    console.log('Primary route failed, trying fallback:', error.message);
                    const response = await fetch(fallbackUrl, {
                        method: 'GET',
                        headers
                    });

                    if (!response.ok || response.headers.get('content-type')?.includes('text/html')) {
                        throw new Error('Both routes failed');
                    }

                    return response;
                }
            }

            extractProductsFromResponse(data) {
                if (data.success && data.products) {
                    return data.products;
                } else if (data.data && Array.isArray(data.data)) {
                    return data.data;
                } else if (Array.isArray(data)) {
                    return data;
                } else if (data.products && Array.isArray(data.products)) {
                    return data.products;
                }
                return [];
            }

            createProductListItem(product) {
                const li = document.createElement('li');
                li.dataset.id = product.id;
                li.dataset.product = product.name || product.product_name;
                li.dataset.name = (product.name || product.product_name || '').toLowerCase();
                li.dataset.price = this.getProductPrice(product);

                const totalQuantity = this.getTotalQuantity(product);
                li.dataset.stock = totalQuantity;

                const stockClass = this.getStockClass(product);
                li.className = stockClass;

                li.innerHTML = `
            <span class="product-name">${product.product_name || product.name}</span>
            <span class="product-details">
                ${product.form_type ? `<span class="product-form">${product.form_type}</span>` : ''}
                ${product.dosage_unit ? `<span class="product-dosage">${product.dosage_unit}</span>` : ''}
                ${(product.form_type || product.dosage_unit) ? '<span class="separator">•</span>' : ''}
                ${product.manufacturer ? `<span class="manufacturer">${product.manufacturer}</span>` : ''}
            </span>
            <span class="product-price">₱${this.getProductPrice(product)}</span>
            <span class="product-stock ${this.getStockClass(product)}">${this.getStockText(product)}</span>
            <span class="batch-info">${this.getBatchInfo(product)}</span>
            <span class="expiration-info">${this.getExpirationInfo(product)}</span>
        `;

                return li;
            }

            // Product helper methods
            getTotalQuantity(product) {
                if (product.batches && Array.isArray(product.batches)) {
                    return product.batches
                        .filter(batch => batch.quantity_remaining > 0)
                        .reduce((total, batch) => total + parseInt(batch.quantity_remaining), 0);
                }
                return product.stock || product.quantity || product.total_stock || 0;
            }

            getStockClass(product) {
                const totalQuantity = this.getTotalQuantity(product);
                const reorderLevel = product.reorder_level || 10;

                if (totalQuantity === 0) {
                    return 'out-of-stock';
                } else if (totalQuantity <= reorderLevel) {
                    return 'low-stock';
                } else {
                    return 'in-stock';
                }
            }

            getStockText(product) {
                const totalQuantity = this.getTotalQuantity(product);
                const activeBatches = this.getActiveBatches(product);

                if (totalQuantity === 0) {
                    return 'Out of Stock';
                } else {
                    return `${totalQuantity} available${activeBatches > 1 ? ` (${activeBatches} batches)` : ''}`;
                }
            }

            getActiveBatches(product) {
                if (product.batches && Array.isArray(product.batches)) {
                    return product.batches.filter(batch => batch.quantity_remaining > 0).length;
                }
                return 0;
            }

            getProductPrice(product) {
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

            getBatchInfo(product) {
                if (!product.batches || !Array.isArray(product.batches)) return '';

                const activeBatches = product.batches.filter(batch => batch.quantity_remaining > 0);
                if (activeBatches.length === 0) return '';

                const nextBatch = activeBatches.sort((a, b) => new Date(a.expiration_date) - new Date(b
                    .expiration_date))[0];

                return `<small class="next-batch">Next: ${nextBatch.batch_number} (${nextBatch.quantity_remaining} units)</small>`;
            }

            getExpirationInfo(product) {
                if (!product.earliest_expiration && (!product.batches || !Array.isArray(product.batches))) return '';

                let earliestExpiry = product.earliest_expiration;

                if (!earliestExpiry && product.batches && Array.isArray(product.batches)) {
                    const activeBatches = product.batches.filter(batch => batch.quantity_remaining > 0);
                    if (activeBatches.length > 0) {
                        earliestExpiry = activeBatches
                            .map(batch => batch.expiration_date)
                            .sort((a, b) => new Date(a) - new Date(b))[0];
                    }
                }

                if (!earliestExpiry) return '';

                const expiryClass = this.getExpiryClass(earliestExpiry);
                const formattedDate = this.formatDate(earliestExpiry);

                return `<small class="expiry-date ${expiryClass}">Expires: ${formattedDate}</small>`;
            }

            formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            getExpiryClass(expirationDate) {
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

            updateSelectedProductsDisplay() {
                if (!this.elements.selectedList || !this.state.currentPrescriptionId) return;

                this.elements.selectedList.innerHTML = '';
                const selectedProducts = this.state.selectedProductsByOrder[this.state.currentPrescriptionId] || [];

                // Clear search input and show all available products
                if (this.elements.productSearchInput) {
                    this.elements.productSearchInput.value = '';
                }

                document.querySelectorAll('#availableProducts li').forEach(li => {
                    li.style.display = 'block';
                });

                // Process selected products
                selectedProducts.forEach(product => {
                    // Hide from available list
                    const availableLi = document.querySelector(
                    `#availableProducts li[data-id="${product.id}"]`);
                    if (availableLi) {
                        availableLi.style.display = 'none';
                    }

                    // Create selected product item
                    const li = document.createElement('li');
                    li.dataset.id = product.id.toString();
                    li.style.cssText =
                        'display: flex; justify-content: space-between; align-items: center; padding: 8px; margin: 4px 0; background: #f8f9fa; border-radius: 4px;';

                    li.innerHTML = `
                <span style="flex: 1;">${product.name} — Qty: ${product.quantity}</span>
                <button class="remove-btn" data-id="${product.id}" type="button"
                        style="margin-left: 10px; padding: 4px 8px; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; min-width: 24px; height: 24px;">
                    ❌
                </button>
            `;

                    this.elements.selectedList.appendChild(li);
                });
            }

            async loadSavedProducts(prescriptionId) {
                if (!this.state.selectedProductsByOrder[prescriptionId]) {
                    this.state.selectedProductsByOrder[prescriptionId] = [];
                }

                try {
                    const response = await fetch(`/prescriptions/${prescriptionId}/items`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken()
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.items) {
                            this.state.selectedProductsByOrder[prescriptionId] = data.items.map(item => ({
                                id: item.product_id,
                                name: item.product_name || item.name,
                                price: item.unit_price || item.price,
                                quantity: item.quantity
                            }));
                        }
                    }
                } catch (error) {
                    console.log('No saved products or error:', error.message);
                    this.state.selectedProductsByOrder[prescriptionId] = [];
                }

                this.updateSelectedProductsDisplay();
            }

            async saveProductSelection(e) {
                e.preventDefault();

                const selectedProducts = this.state.selectedProductsByOrder[this.state.currentPrescriptionId] || [];

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

                try {
                    const response = await fetch(`/prescriptions/${this.state.currentPrescriptionId}/save-selection`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getCSRFToken(),
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
                    });

                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Server error:', text);
                        throw new Error(`Server error: ${response.status} - ${text}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message || 'Products saved successfully!');
                        this.closeManageOrderModal();
                    } else {
                        alert(data.message || 'Failed to save products. Please try again.');
                    }
                } catch (error) {
                    console.error('Error saving products:', error);
                    alert('An error occurred while saving products: ' + error.message);
                } finally {
                    if (saveBtn) {
                        saveBtn.textContent = originalText;
                        saveBtn.disabled = false;
                    }
                }
            }

            // Prescription Viewer Management
            resetPrescriptionViewer() {
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

            togglePrescriptionViewer() {
                const container = document.getElementById('prescriptionReferenceContainer');
                const toggleBtn = document.getElementById('togglePrescriptionBtn');
                const refreshBtn = document.getElementById('refreshPrescriptionBtn');

                if (container.style.display === 'none') {
                    if (this.state.currentManageOrderData.hasDocument) {
                        container.style.display = 'block';
                        toggleBtn.textContent = 'Hide Prescription';
                        refreshBtn.style.display = 'inline-block';
                        this.loadPrescriptionReference();
                    } else {
                        container.style.display = 'block';
                        toggleBtn.textContent = 'Hide Prescription';
                    }
                } else {
                    container.style.display = 'none';
                    toggleBtn.textContent = 'Show Prescription';
                    refreshBtn.style.display = 'none';
                }
            }

            loadPrescriptionReference() {
                if (!this.state.currentManageOrderData.hasDocument) return;

                const loadingRef = document.getElementById('prescriptionLoadingRef');
                const contentRef = document.getElementById('prescriptionContentRef');
                const errorRef = document.getElementById('prescriptionErrorRef');

                if (loadingRef) loadingRef.style.display = 'flex';
                if (contentRef) contentRef.style.display = 'none';
                if (errorRef) errorRef.style.display = 'none';

                const {
                    documentType,
                    documentUrl
                } = this.state.currentManageOrderData;

                setTimeout(() => {
                    if (documentType === 'image' || documentType === 'legacy') {
                        const img = new Image();
                        img.onload = function() {
                            if (contentRef) {
                                contentRef.innerHTML =
                                    `<img src="${documentUrl}" alt="Prescription Document" style="max-width: 100%; height: auto;" />`;
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
                                `<iframe src="${documentUrl}" title="Prescription PDF" style="width: 100%; height: 400px; border: none;"></iframe>`;
                            contentRef.style.display = 'block';
                        }
                        if (loadingRef) loadingRef.style.display = 'none';
                    } else {
                        this.showPrescriptionReferenceError();
                    }
                }, 300);
            }

            showPrescriptionReferenceError() {
                const loadingRef = document.getElementById('prescriptionLoadingRef');
                const contentRef = document.getElementById('prescriptionContentRef');
                const errorRef = document.getElementById('prescriptionErrorRef');

                if (loadingRef) loadingRef.style.display = 'none';
                if (contentRef) contentRef.style.display = 'none';
                if (errorRef) errorRef.style.display = 'flex';
            }

            // Enhanced Prescription Viewer with Zoom
            viewPrescriptionInModal(id, type, filename, viewUrl, downloadUrl) {
                this.state.currentPrescriptionData = {
                    id,
                    type,
                    filename,
                    viewUrl,
                    downloadUrl
                };

                const modal = this.elements.prescriptionModal;
                const orderIdSpan = document.getElementById('prescriptionModalOrderId');
                const loadingDiv = document.getElementById('prescriptionLoading');
                const contentDiv = document.getElementById('prescriptionContent');
                const errorDiv = document.getElementById('prescriptionError');

                if (!modal) {
                    console.error('Prescription modal not found!');
                    return;
                }

                if (orderIdSpan) orderIdSpan.textContent = filename;

                modal.style.display = 'flex';
                modal.classList.add('active');

                if (loadingDiv) loadingDiv.style.display = 'flex';
                if (contentDiv) contentDiv.style.display = 'none';
                if (errorDiv) errorDiv.style.display = 'none';

                setTimeout(() => {
                    this.loadPrescriptionContentWithZoom(type, viewUrl);
                }, 500);
            }

            loadPrescriptionContentWithZoom(type, viewUrl) {
                const loadingDiv = document.getElementById('prescriptionLoading');
                const contentDiv = document.getElementById('prescriptionContent');
                const errorDiv = document.getElementById('prescriptionError');

                if (type === 'image' || type === 'legacy') {
                    const img = new Image();
                    img.onload = () => {
                        if (contentDiv) {
                            // Create enhanced image container with zoom functionality
                            contentDiv.innerHTML = `
                        <div class="prescription-image-container" style="
                            position: relative;
                            width: 100%;
                            height: 80vh;
                            overflow: hidden;
                            background: #f5f5f5;
                            border-radius: 8px;
                            cursor: zoom-in;
                        ">
                            <img id="prescriptionImage"
                                 src="${viewUrl}"
                                 alt="Prescription Document"
                                 style="
                                     width: 100%;
                                     height: 100%;
                                     object-fit: contain;
                                     transition: transform 0.3s ease;
                                     transform-origin: center center;
                                 "
                            />
                            <div class="zoom-controls" style="
                                position: absolute;
                                top: 10px;
                                right: 10px;
                                background: rgba(0,0,0,0.7);
                                border-radius: 20px;
                                padding: 5px;
                                display: flex;
                                gap: 5px;
                            ">
                                <button id="zoomIn" style="
                                    background: white;
                                    border: none;
                                    border-radius: 50%;
                                    width: 32px;
                                    height: 32px;
                                    cursor: pointer;
                                    font-size: 16px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">+</button>
                                <button id="zoomOut" style="
                                    background: white;
                                    border: none;
                                    border-radius: 50%;
                                    width: 32px;
                                    height: 32px;
                                    cursor: pointer;
                                    font-size: 16px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">−</button>
                                <button id="resetZoom" style="
                                    background: white;
                                    border: none;
                                    border-radius: 50%;
                                    width: 32px;
                                    height: 32px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">⌂</button>
                            </div>
                        </div>
                    `;

                            this.initializeImageZoom();
                            contentDiv.style.display = 'flex';
                        }
                        if (loadingDiv) loadingDiv.style.display = 'none';
                    };

                    img.onerror = () => this.showPrescriptionError();
                    img.src = viewUrl;

                } else if (type === 'pdf') {
                    if (contentDiv) {
                        contentDiv.innerHTML = `
                    <div class="prescription-pdf-container" style="
                        width: 100%;
                        height: 80vh;
                        border-radius: 8px;
                        overflow: hidden;
                    ">
                        <iframe src="${viewUrl}"
                                title="Prescription PDF"
                                style="width: 100%; height: 100%; border: none;">
                        </iframe>
                    </div>
                `;
                        contentDiv.style.display = 'flex';
                    }
                    if (loadingDiv) loadingDiv.style.display = 'none';
                } else {
                    this.showPrescriptionError();
                }
            }

            initializeImageZoom() {
                const img = document.getElementById('prescriptionImage');
                const container = img?.parentElement;

                if (!img || !container) return;

                let scale = 1;
                let isDragging = false;
                let startX = 0;
                let startY = 0;
                let translateX = 0;
                let translateY = 0;

                // Zoom controls
                document.getElementById('zoomIn')?.addEventListener('click', () => {
                    scale = Math.min(scale + 0.5, 5);
                    this.updateImageTransform(img, scale, translateX, translateY);
                });

                document.getElementById('zoomOut')?.addEventListener('click', () => {
                    scale = Math.max(scale - 0.5, 0.5);
                    this.updateImageTransform(img, scale, translateX, translateY);
                });

                document.getElementById('resetZoom')?.addEventListener('click', () => {
                    scale = 1;
                    translateX = 0;
                    translateY = 0;
                    this.updateImageTransform(img, scale, translateX, translateY);
                });

                // Mouse wheel zoom
                container.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    const delta = e.deltaY > 0 ? -0.1 : 0.1;
                    scale = Math.max(0.5, Math.min(5, scale + delta));
                    this.updateImageTransform(img, scale, translateX, translateY);
                });

                // Double click to zoom
                img.addEventListener('dblclick', (e) => {
                    e.preventDefault();
                    if (scale === 1) {
                        scale = 2;
                    } else {
                        scale = 1;
                        translateX = 0;
                        translateY = 0;
                    }
                    this.updateImageTransform(img, scale, translateX, translateY);
                });

                // Drag to pan (only when zoomed)
                img.addEventListener('mousedown', (e) => {
                    if (scale > 1) {
                        isDragging = true;
                        startX = e.clientX - translateX;
                        startY = e.clientY - translateY;
                        img.style.cursor = 'grabbing';
                    }
                });

                document.addEventListener('mousemove', (e) => {
                    if (isDragging && scale > 1) {
                        translateX = e.clientX - startX;
                        translateY = e.clientY - startY;
                        this.updateImageTransform(img, scale, translateX, translateY);
                    }
                });

                document.addEventListener('mouseup', () => {
                    if (isDragging) {
                        isDragging = false;
                        img.style.cursor = scale > 1 ? 'grab' : 'zoom-in';
                    }
                });

                // Update cursor based on zoom level
                img.style.cursor = scale > 1 ? 'grab' : 'zoom-in';
            }

            updateImageTransform(img, scale, translateX, translateY) {
                img.style.transform = `scale(${scale}) translate(${translateX/scale}px, ${translateY/scale}px)`;
                img.style.cursor = scale > 1 ? 'grab' : 'zoom-in';
            }

            showPrescriptionError() {
                const loadingDiv = document.getElementById('prescriptionLoading');
                const contentDiv = document.getElementById('prescriptionContent');
                const errorDiv = document.getElementById('prescriptionError');

                if (loadingDiv) loadingDiv.style.display = 'none';
                if (contentDiv) contentDiv.style.display = 'none';
                if (errorDiv) errorDiv.style.display = 'flex';
            }

            closePrescriptionViewer() {
                const modal = this.elements.prescriptionModal;
                const contentDiv = document.getElementById('prescriptionContent');

                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                }

                if (contentDiv) {
                    contentDiv.innerHTML = '';
                }

                this.state.currentPrescriptionData = {
                    id: null,
                    type: null,
                    filename: null,
                    viewUrl: null,
                    downloadUrl: null
                };
            }

            // Order Completion
            async openCompleteOrderModal(prescriptionId) {
                this.closeAllDropdowns();
                this.state.currentOrderPrescriptionId = prescriptionId;

                try {
                    const response = await fetch(`/orders/${prescriptionId}/summary`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken()
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (this.elements.orderSummary) {
                        if (data.success && data.items && data.items.length > 0) {
                            this.renderOrderSummary(data);
                        } else {
                            this.elements.orderSummary.innerHTML =
                                '<p>No products selected yet. Please manage the order first.</p>';
                        }
                    }

                    if (this.elements.completeOrderModal) {
                        this.elements.completeOrderModal.style.display = 'block';
                        this.elements.completeOrderModal.classList.add('active');
                    }

                } catch (error) {
                    console.error('Error loading order summary:', error);
                    alert(`Error loading order summary: ${error.message}. Please ensure products are saved first.`);
                }
            }

            renderOrderSummary(data) {
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

                this.elements.orderSummary.innerHTML = summaryHTML;
            }

            closeCompleteOrderModal() {
                if (this.elements.completeOrderModal) {
                    this.elements.completeOrderModal.style.display = 'none';
                    this.elements.completeOrderModal.classList.remove('active');
                }
                this.state.currentOrderPrescriptionId = null;
            }

            async submitOrderCompletion(e) {
                e.preventDefault();

                if (!this.state.currentOrderPrescriptionId) {
                    alert('Error: No prescription selected. Please try again.');
                    return;
                }

                const originalText = this.elements.submitOrderBtn.textContent;
                this.elements.submitOrderBtn.textContent = 'Processing...';
                this.elements.submitOrderBtn.disabled = true;

                try {
                    const paymentMethodElement = document.getElementById('paymentMethod');
                    const orderNotesElement = document.getElementById('orderNotes');

                    const paymentMethod = paymentMethodElement ? paymentMethodElement.value : 'cash';
                    const orderNotes = orderNotesElement ? orderNotesElement.value.trim() : '';

                    const response = await fetch(`/orders/${this.state.currentOrderPrescriptionId}/complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            payment_method: paymentMethod,
                            notes: orderNotes
                        })
                    });

                    const responseText = await response.text();
                    let data;

                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error(
                            `Server returned invalid JSON. Status: ${response.status}, Response: ${responseText.substring(0, 200)}...`
                            );
                    }

                    if (response.ok && data.success) {
                        alert(
                            `Order completed successfully!\n\nSale ID: ${data.sale_id}\nTotal Amount: ₱${parseFloat(data.total_amount).toFixed(2)}\nTotal Items: ${data.total_items}\nPayment Method: ${data.payment_method}\n\nStock has been updated automatically.`);

                        this.closeCompleteOrderModal();
                        window.location.reload();
                    } else {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    }

                } catch (error) {
                    console.error('Error completing order:', error);
                    alert(`Failed to complete order: ${error.message}`);
                } finally {
                    this.elements.submitOrderBtn.textContent = originalText;
                    this.elements.submitOrderBtn.disabled = false;
                }
            }

            // Chat Functionality
            openChatModal(prescriptionId, triggerElement) {
                this.closeAllDropdowns();
                this.state.currentChatPrescriptionId = prescriptionId;

                const orderRow = triggerElement.closest('.order-row');
                const orderIdElement = orderRow.querySelector('strong');
                if (orderIdElement && this.elements.chatOrderId) {
                    this.elements.chatOrderId.textContent = `Chat - ${orderIdElement.textContent}`;
                }

                if (this.elements.chatModal) {
                    this.elements.chatModal.classList.add('active');
                    this.loadMessages(prescriptionId);
                    this.markMessagesAsRead(prescriptionId);
                }
            }

            closeChatModal() {
                if (this.elements.chatModal) {
                    this.elements.chatModal.classList.remove('active');
                }
                this.state.currentChatPrescriptionId = null;
            }

            async loadMessages(prescriptionId) {
                if (!this.elements.chatMessages) return;

                this.elements.chatMessages.innerHTML = '<div class="no-messages">Loading messages...</div>';

                try {
                    const response = await fetch(`/admin/orders/${prescriptionId}/messages`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken()
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.displayMessages(data.messages);
                    }
                } catch (error) {
                    console.error('Error loading messages:', error);
                    this.elements.chatMessages.innerHTML = '<div class="no-messages">Error loading messages</div>';
                }
            }

            displayMessages(messages) {
                if (!this.elements.chatMessages) return;

                if (messages.length === 0) {
                    this.elements.chatMessages.innerHTML =
                        '<div class="no-messages">No messages yet. Start the conversation!</div>';
                    return;
                }

                this.elements.chatMessages.innerHTML = '';

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

                    this.elements.chatMessages.appendChild(messageDiv);
                });

                // Scroll to bottom
                this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
            }

            async sendMessage() {
                if (!this.state.currentChatPrescriptionId || !this.elements.chatInput) return;

                const message = this.elements.chatInput.value.trim();
                if (!message) return;

                const originalText = this.elements.sendButton ? this.elements.sendButton.innerHTML : '';
                if (this.elements.sendButton) {
                    this.elements.sendButton.disabled = true;
                    this.elements.sendButton.innerHTML = '...';
                }

                try {
                    const response = await fetch(`/admin/orders/${this.state.currentChatPrescriptionId}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            message: message
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.elements.chatInput.value = '';

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

                        if (this.elements.chatMessages.querySelector('.no-messages')) {
                            this.elements.chatMessages.innerHTML = '';
                        }

                        this.elements.chatMessages.appendChild(messageDiv);
                        this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                } finally {
                    if (this.elements.sendButton) {
                        this.elements.sendButton.disabled = false;
                        this.elements.sendButton.innerHTML = originalText;
                    }
                }
            }

            async markMessagesAsRead(prescriptionId) {
                try {
                    await fetch(`/admin/orders/${prescriptionId}/messages/mark-read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getCSRFToken(),
                            'Accept': 'application/json'
                        }
                    });
                } catch (error) {
                    console.error('Error marking messages as read:', error);
                }
            }
        }

        // Initialize the system when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Create global instance
            window.orderManager = new OrderManagementSystem();

            // Make loadAvailableProducts globally accessible for retry buttons
            window.loadAvailableProducts = () => {
                if (window.orderManager) {
                    window.orderManager.loadAvailableProducts();
                }
            };
        });
    </script>
    @stack('scripts')
</body>

</html>
