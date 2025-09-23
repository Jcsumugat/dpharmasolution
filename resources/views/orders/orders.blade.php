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
                                    <button class="dropdown-trigger" data-id="{{ $prescription->id }}">
                                        &#8943;
                                    </button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item process-complete-btn"
                                            data-id="{{ $prescription->id }}">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M9 11l3 3L22 4" />
                                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                                            </svg>
                                            Process Order
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item cancel-order-btn"
                                            data-id="{{ $prescription->id }}">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10" />
                                                <line x1="15" y1="9" x2="9" y2="15" />
                                                <line x1="9" y1="9" x2="15" y2="15" />
                                            </svg>
                                            Cancel Order
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
                    <button id="saveSelection" class="btn btn-primary">Complete Order</button>
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
        class OrderManagementSystem {
            constructor() {
                this.state = {
                    currentPrescriptionId: null,
                    selectedProductLi: null,
                    selectedProductsByOrder: {},
                    currentManageOrderData: {
                        prescriptionId: null,
                        hasDocument: false,
                        documentType: null,
                        documentUrl: null
                    },
                    currentPrescriptionData: null,
                    isLoading: false,
                    cache: {
                        products: null,
                        lastProductFetch: 0
                    }
                };
                this.elements = {};
                this.debounceTimers = {};
                this.CACHE_DURATION = 5 * 60 * 1000; // 5 minutes
                this.init();
            }

            init() {
                this.cacheElements();
                this.bindEvents();
                this.filterOrders();
                this.preloadCriticalData();
            }

            cacheElements() {
                // Use object mapping for better performance
                const elementMap = {
                    // Modal elements
                    dropdownOverlay: '.dropdown-overlay',
                    manageModal: '#manageOrderModal',
                    qtyModal: '#productQuantityModal',
                    prescriptionModal: '#prescriptionViewerModal',

                    // Product management elements
                    availableList: '#availableProducts',
                    selectedList: '#selectedProducts',
                    productSearchInput: '#productSearch',
                    qtyInput: '#productQty',
                    productModalName: '#productModalName',

                    // Search and filter elements
                    searchInput: '#order-search',
                    statusFilter: '#status-filter',
                    typeFilters: '.filter-btn[data-filter]'
                };

                for (const [key, selector] of Object.entries(elementMap)) {
                    if (key === 'typeFilters') {
                        this.elements[key] = document.querySelectorAll(selector);
                    } else {
                        this.elements[key] = document.querySelector(selector);
                    }
                }
            }

            bindEvents() {
                this.bindGlobalEvents();
                this.bindSearchAndFilter();
                this.bindDropdownEvents();
                this.bindModalEvents();
                this.bindOrderActions();
                this.bindProductManagement();
                this.bindPrescriptionViewer();
            }

            bindGlobalEvents() {
                document.addEventListener('keydown', (e) => {
                    if (e.key === "Escape") {
                        this.closeAllDropdowns();
                        this.closeAllModals();
                    }
                });

                // Handle visibility change to refresh data when tab becomes active
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && this.shouldRefreshData()) {
                        this.refreshOrderData();
                    }
                });
            }

            bindSearchAndFilter() {
                if (this.elements.searchInput) {
                    this.elements.searchInput.addEventListener('input',
                        this.debounce(() => this.filterOrders(), 300)
                    );
                }

                if (this.elements.statusFilter) {
                    this.elements.statusFilter.addEventListener('change', () => this.filterOrders());
                }

                this.elements.typeFilters.forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.elements.typeFilters.forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        this.filterOrders();
                        this.updateOrderStats();
                    });
                });
            }

            bindDropdownEvents() {
                // Use event delegation for better performance
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
                const modals = [this.elements.manageModal, this.elements.qtyModal, this.elements.prescriptionModal];

                modals.forEach(modal => {
                    if (modal) {
                        modal.addEventListener('click', (e) => {
                            if (e.target === e.currentTarget) {
                                this.closeModal(modal);
                            }
                        });
                    }
                });

                // Bind cancel buttons efficiently
                const cancelButtons = [
                    ['#cancelManageOrder', () => this.closeManageOrderModal()],
                    ['#cancelQtyModal', () => this.closeQtyModal()],
                    ['#manageOrderModal .modal-close', () => this.closeManageOrderModal()]
                ];

                cancelButtons.forEach(([selector, handler]) => {
                    const element = document.querySelector(selector);
                    if (element) element.addEventListener('click', handler);
                });
            }

            bindOrderActions() {
                // Use event delegation for dynamically generated content
                document.addEventListener('click', (e) => {
                    const target = e.target;
                    const prescriptionId = target.dataset.id;

                    if (target.classList.contains('process-complete-btn')) {
                        e.preventDefault();
                        this.processOrder(prescriptionId);
                    } else if (target.classList.contains('cancel-order-btn')) {
                        e.preventDefault();
                        this.cancelOrder(prescriptionId);
                    }
                });
            }

            // OPTIMIZED PRODUCT MANAGEMENT - This replaces your existing bindProductManagement method
            bindProductManagement() {
                if (this.elements.productSearchInput) {
                    this.elements.productSearchInput.addEventListener('input',
                        this.debounce(() => this.handleProductSearch(), 200)
                    );
                }

                // Improved event delegation for available products
                if (this.elements.availableList) {
                    this.elements.availableList.addEventListener('click', (e) => {
                        // Find the closest LI element (handles clicks on child elements)
                        const productLi = e.target.closest('li.product-item');

                        if (productLi && !productLi.classList.contains('no-search-results') &&
                            !productLi.classList.contains('loading-item') &&
                            !productLi.classList.contains('error-item')) {

                            e.preventDefault();
                            e.stopPropagation();
                            this.handleProductSelection(productLi);
                        }
                    });

                    // Add keyboard support for accessibility
                    this.elements.availableList.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            const productLi = e.target.closest('li.product-item');
                            if (productLi) {
                                e.preventDefault();
                                this.handleProductSelection(productLi);
                            }
                        }
                    });
                }

                // Improved event delegation for selected products
                if (this.elements.selectedList) {
                    this.elements.selectedList.addEventListener('click', (e) => {
                        const removeBtn = e.target.closest('.remove-btn');
                        if (removeBtn) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.handleProductRemoval(removeBtn);
                        }
                    });
                }

                // Bind other controls
                this.bindQuantityControls();
                this.bindModalControls();
            }

            // New optimized product selection handler
            handleProductSelection(productLi) {
                const stockLevel = parseInt(productLi.dataset.stock) || 0;

                if (stockLevel <= 0) {
                    this.showStockMessage('This product is out of stock.', 'error');
                    return;
                }

                // Check if already selected
                const productId = productLi.dataset.id;
                const selectedProducts = this.state.selectedProductsByOrder[this.state.currentPrescriptionId] || [];
                const alreadySelected = selectedProducts.find(p => p.id.toString() === productId.toString());

                if (alreadySelected) {
                    this.showStockMessage('This product is already selected. Remove it first to reselect.', 'warning');
                    return;
                }

                // Store reference and show quantity modal
                this.state.selectedProductLi = productLi;
                this.openQuantityModal(productLi, stockLevel);
            }

            // Separate product removal handler
            handleProductRemoval(removeBtn) {
                const productId = removeBtn.dataset.id;
                const productLi = removeBtn.closest('li');
                const productName = productLi?.querySelector('.selected-product-name')?.textContent || 'this product';

                if (confirm(`Remove "${productName}" from selection?`)) {
                    this.removeSelectedProduct(productId);
                }
            }

            // Optimized quantity modal opening
            openQuantityModal(productLi, stockLevel) {
                if (this.elements.qtyInput) {
                    this.elements.qtyInput.value = 1;
                    this.elements.qtyInput.max = stockLevel;
                    this.elements.qtyInput.min = 1;
                }

                if (this.elements.productModalName) {
                    this.elements.productModalName.textContent = productLi.dataset.product || 'Product';
                }

                if (this.elements.qtyModal) {
                    this.elements.qtyModal.style.display = 'flex';
                    // Focus on quantity input for better UX
                    setTimeout(() => {
                        if (this.elements.qtyInput) {
                            this.elements.qtyInput.focus();
                            this.elements.qtyInput.select();
                        }
                    }, 100);
                }
            }

            // Improved stock message display
            showStockMessage(message, type = 'info') {
                // Remove existing messages
                const existingMessage = document.querySelector('.stock-message');
                if (existingMessage) {
                    existingMessage.remove();
                }

                const messageElement = document.createElement('div');
                messageElement.className = `stock-message stock-message-${type}`;
                messageElement.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: ${type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#2196f3'};
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            max-width: 300px;
            text-align: center;
            animation: fadeInOut 3s ease-in-out forwards;
        `;
                messageElement.textContent = message;

                // Add CSS animation if not exists
                if (!document.querySelector('#stockMessageStyles')) {
                    const style = document.createElement('style');
                    style.id = 'stockMessageStyles';
                    style.textContent = `
                @keyframes fadeInOut {
                    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
                    15% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                    85% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                    100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
                }
            `;
                    document.head.appendChild(style);
                }

                document.body.appendChild(messageElement);

                setTimeout(() => {
                    messageElement.remove();
                }, 3000);
            }

            // Separate quantity controls binding for clarity
            bindQuantityControls() {
                const controls = [
                    ['#increaseQty', () => this.increaseQuantity()],
                    ['#decreaseQty', () => this.decreaseQuantity()],
                    ['#confirmQtyModal', () => this.confirmQuantitySelection()],
                    ['#cancelQtyModal', () => this.closeQtyModal()]
                ];

                controls.forEach(([selector, handler]) => {
                    const element = document.querySelector(selector);
                    if (element) {
                        // Remove existing listeners to prevent duplicates
                        const newHandler = handler.bind(this);
                        element.removeEventListener('click', newHandler);
                        element.addEventListener('click', newHandler);
                    }
                });

                if (this.elements.qtyInput) {
                    const inputHandler = () => this.validateQuantityInput();
                    this.elements.qtyInput.removeEventListener('input', inputHandler);
                    this.elements.qtyInput.addEventListener('input', inputHandler);

                    // Add enter key support
                    const keyHandler = (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            this.confirmQuantitySelection();
                        }
                    };
                    this.elements.qtyInput.removeEventListener('keydown', keyHandler);
                    this.elements.qtyInput.addEventListener('keydown', keyHandler);
                }
            }

            // Separate modal controls binding
            bindModalControls() {
                const modalControls = [
                    ['#saveSelection', (e) => this.saveAndCompleteOrder(e)],
                    ['#togglePrescriptionBtn', () => this.togglePrescriptionViewer()],
                    ['#refreshPrescriptionBtn', () => this.loadPrescriptionReference()]
                ];

                modalControls.forEach(([selector, handler]) => {
                    const element = document.querySelector(selector);
                    if (element) {
                        const boundHandler = handler.bind(this);
                        element.removeEventListener('click', boundHandler);
                        element.addEventListener('click', boundHandler);
                    }
                });
            }

            bindPrescriptionViewer() {
                // Global functions for onclick handlers
                window.viewPrescriptionInModal = (id, type, filename, viewUrl, downloadUrl) => {
                    this.viewPrescriptionInModal(id, type, filename, viewUrl, downloadUrl);
                };

                window.closePrescriptionViewer = () => this.closePrescriptionViewer();
                window.retryLoadPrescription = () => this.loadPrescriptionReference();
                window.closeManageOrderModal = () => this.closeManageOrderModal();

                const downloadBtn = document.getElementById('downloadPrescriptionBtn');
                if (downloadBtn) {
                    downloadBtn.addEventListener('click', () => {
                        if (this.state.currentPrescriptionData?.downloadUrl) {
                            window.open(this.state.currentPrescriptionData.downloadUrl, '_blank');
                        }
                    });
                }
            }

            // Utility Methods
            debounce(func, wait) {
                return (...args) => {
                    const key = func.name || 'anonymous';
                    clearTimeout(this.debounceTimers[key]);
                    this.debounceTimers[key] = setTimeout(() => func.apply(this, args), wait);
                };
            }

            getCSRFToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            }

            shouldRefreshData() {
                return Date.now() - this.state.cache.lastProductFetch > this.CACHE_DURATION;
            }

            async preloadCriticalData() {
                // Preload products for better UX
                if (!this.state.cache.products) {
                    try {
                        await this.loadAvailableProducts(false); // Don't show loading state
                    } catch (error) {
                        console.warn('Failed to preload products:', error);
                    }
                }
            }

            async refreshOrderData() {
                // Refresh order statistics and update UI
                try {
                    const response = await fetch('/admin/orders/stats', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken()
                        }
                    });
                    if (response.ok) {
                        const stats = await response.json();
                        this.updateOrderStatsDisplay(stats);
                    }
                } catch (error) {
                    console.warn('Failed to refresh order data:', error);
                }
            }

            updateOrderStatsDisplay(stats) {
                const statElements = {
                    'total-orders': stats.total,
                    'pending-orders': stats.pending,
                    'prescription-count': stats.prescriptions,
                    'online-order-count': stats.online_orders
                };

                Object.entries(statElements).forEach(([id, value]) => {
                    const element = document.getElementById(id);
                    if (element && value !== undefined) {
                        element.textContent = value;
                    }
                });
            }

            closeAllDropdowns() {
                const activeMenus = document.querySelectorAll('.dropdown-menu.show');
                activeMenus.forEach(menu => menu.classList.remove('show'));

                if (this.elements.dropdownOverlay) {
                    this.elements.dropdownOverlay.style.display = 'none';
                }
            }

            closeAllModals() {
                const modals = [this.elements.manageModal, this.elements.qtyModal, this.elements.prescriptionModal];
                modals.forEach(modal => {
                    if (modal) this.closeModal(modal);
                });
                this.resetState();
            }

            closeModal(modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }

            resetState() {
                Object.assign(this.state, {
                    currentPrescriptionId: null,
                    selectedProductLi: null,
                    currentPrescriptionData: null
                });
            }

            // Enhanced Filter and Search
            filterOrders() {
                const searchTerm = this.elements.searchInput?.value.toLowerCase() || '';
                const statusFilterValue = this.elements.statusFilter?.value || 'all';
                const activeTypeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';

                const rows = document.querySelectorAll('.order-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const searchData = row.dataset.search || '';
                    const orderStatus = row.dataset.status;
                    const orderType = row.dataset.orderType;

                    const matchesSearch = !searchTerm || searchData.includes(searchTerm);
                    const matchesStatus = statusFilterValue === 'all' || orderStatus === statusFilterValue;
                    const matchesType = activeTypeFilter === 'all' || orderType === activeTypeFilter;

                    const shouldShow = matchesSearch && matchesStatus && matchesType;

                    row.style.display = shouldShow ? '' : 'none';
                    if (shouldShow) visibleCount++;
                });

                // Update visible count indicator
                this.updateFilterResultsIndicator(visibleCount, rows.length);
            }

            updateFilterResultsIndicator(visible, total) {
                let indicator = document.querySelector('.filter-results-indicator');

                if (!indicator) {
                    indicator = document.createElement('div');
                    indicator.className = 'filter-results-indicator';
                    indicator.style.cssText = 'font-size: 0.9em; color: #666; margin: 10px 0;';

                    const tableWrapper = document.querySelector('.table-wrapper');
                    if (tableWrapper) {
                        tableWrapper.parentNode.insertBefore(indicator, tableWrapper);
                    }
                }

                if (visible === total) {
                    indicator.textContent = `Showing all ${total} orders`;
                } else {
                    indicator.textContent = `Showing ${visible} of ${total} orders`;
                }
            }

            updateOrderStats() {
                const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
                const visibleRows = document.querySelectorAll('.order-row:not([style*="display: none"])');

                const stats = {
                    total: visibleRows.length,
                    pending: 0,
                    prescription: 0,
                    online_order: 0
                };

                visibleRows.forEach(row => {
                    const status = row.dataset.status;
                    const type = row.dataset.orderType;

                    if (status === 'pending') stats.pending++;
                    if (type === 'prescription') stats.prescription++;
                    if (type === 'online_order') stats.online_order++;
                });

                // Update display if filtering is active
                if (activeFilter !== 'all') {
                    this.updateOrderStatsDisplay({
                        total: stats.total,
                        pending: stats.pending,
                        prescriptions: stats.prescription,
                        online_orders: stats.online_order
                    });
                }
            }

            // Enhanced Dropdown Management
            handleDropdownTrigger(trigger) {
                // Close all other dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== trigger.nextElementSibling) {
                        menu.classList.remove('show');
                    }
                });

                const dropdown = trigger.nextElementSibling;
                if (dropdown?.classList.contains('dropdown-menu')) {
                    const isVisible = dropdown.classList.contains('show');

                    if (isVisible) {
                        dropdown.classList.remove('show');
                        if (this.elements.dropdownOverlay) {
                            this.elements.dropdownOverlay.style.display = 'none';
                        }
                    } else {
                        dropdown.classList.add('show');
                        if (this.elements.dropdownOverlay) {
                            this.elements.dropdownOverlay.style.display = 'block';
                        }

                        // Position dropdown if needed
                        this.positionDropdown(dropdown, trigger);
                    }
                }
            }

            positionDropdown(dropdown, trigger) {
                const rect = trigger.getBoundingClientRect();
                const dropdownRect = dropdown.getBoundingClientRect();
                const viewportHeight = window.innerHeight;

                // Check if dropdown would go below viewport
                if (rect.bottom + dropdownRect.height > viewportHeight) {
                    dropdown.style.top = 'auto';
                    dropdown.style.bottom = '100%';
                } else {
                    dropdown.style.top = '100%';
                    dropdown.style.bottom = 'auto';
                }
            }

            // Enhanced Order Actions
            async processOrder(prescriptionId) {
                if (this.state.isLoading) return;

                this.closeAllDropdowns();
                this.state.currentPrescriptionId = prescriptionId;
                this.state.isLoading = true;

                try {
                    // Get prescription document info from the row
                    const orderRow = document.querySelector(`[data-id="${prescriptionId}"]`)?.closest('.order-row');
                    if (!orderRow) {
                        throw new Error('Order row not found');
                    }

                    const documentCell = orderRow.querySelector('td:nth-child(4)');
                    const viewButton = documentCell?.querySelector('.btn-view');

                    this.state.currentManageOrderData = {
                        prescriptionId: prescriptionId,
                        hasDocument: false,
                        documentType: null,
                        documentUrl: null
                    };

                    if (viewButton?.onclick) {
                        const onclickStr = viewButton.getAttribute('onclick');
                        const match = onclickStr.match(
                            /viewPrescriptionInModal\((\d+),\s*'([^']+)',\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)/);

                        if (match) {
                            this.state.currentManageOrderData = {
                                prescriptionId: prescriptionId,
                                hasDocument: true,
                                documentType: match[2],
                                documentUrl: match[4],
                                filename: match[3]
                            };
                        }
                    }

                    // Update modal title
                    const manageOrderId = document.getElementById('manageOrderId');
                    if (manageOrderId) {
                        const orderIdElement = orderRow.querySelector('strong');
                        manageOrderId.textContent = orderIdElement?.textContent || `Order ${prescriptionId}`;
                    }

                    // Reset and show modal
                    this.resetPrescriptionViewer();

                    if (this.elements.manageModal) {
                        this.elements.manageModal.style.display = 'flex';

                        if (this.elements.productSearchInput) {
                            this.elements.productSearchInput.value = '';
                        }

                        // Load data in parallel for better performance
                        await Promise.all([
                            this.loadAvailableProducts(),
                            this.loadSavedProducts(prescriptionId)
                        ]);
                    }

                } catch (error) {
                    console.error('Error processing order:', error);
                    alert('Failed to load order: ' + error.message);
                } finally {
                    this.state.isLoading = false;
                }
            }

            async cancelOrder(prescriptionId) {
                if (this.state.isLoading) return;

                this.closeAllDropdowns();

                // Get order information for modal title
                const orderRow = document.querySelector(`[data-id="${prescriptionId}"]`)?.closest('.order-row');
                const orderIdElement = orderRow?.querySelector('strong');
                const orderId = orderIdElement?.textContent || `Order ${prescriptionId}`;

                this.showCancelOrderModal(prescriptionId, orderId);
            }

            showCancelOrderModal(prescriptionId, orderId) {
                // Create modal if it doesn't exist
                let cancelModal = document.getElementById('cancelOrderModal');
                if (!cancelModal) {
                    cancelModal = this.createCancelOrderModal();
                    document.body.appendChild(cancelModal);
                }

                // Update modal title and show
                const modalTitle = cancelModal.querySelector('.cancel-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = `Cancel Order - ${orderId}`;
                }

                // Reset form
                const reasonSelect = cancelModal.querySelector('#cancelReason');
                const additionalMessage = cancelModal.querySelector('#additionalMessage');
                if (reasonSelect) reasonSelect.value = '';
                if (additionalMessage) additionalMessage.value = '';

                // Store prescription ID for later use
                cancelModal.dataset.prescriptionId = prescriptionId;

                // Show modal
                cancelModal.style.display = 'flex';

                // Focus on reason dropdown
                setTimeout(() => reasonSelect?.focus(), 100);
            }

            createCancelOrderModal() {
                const modal = document.createElement('div');
                modal.id = 'cancelOrderModal';
                modal.className = 'modal';
                modal.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        `;

                modal.innerHTML = `
            <div class="modal-content cancel-order-content" style="
                background: white;
                border-radius: 8px;
                width: 90%;
                max-width: 500px;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            ">
                <div class="modal-header" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #eee;
                ">
                    <h3 class="cancel-modal-title" style="margin: 0; color: #d32f2f;">Cancel Order</h3>
                    <button class="modal-close cancel-modal-close" style="
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: #666;
                        padding: 0;
                        width: 30px;
                        height: 30px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">&times;</button>
                </div>

                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="cancelReason" style="
                            display: block;
                            margin-bottom: 8px;
                            font-weight: 600;
                            color: #333;
                        ">Reason for Cancellation <span style="color: #d32f2f;">*</span></label>
                        <select id="cancelReason" required style="
                            width: 100%;
                            padding: 10px;
                            border: 2px solid #ddd;
                            border-radius: 4px;
                            font-size: 14px;
                            background: white;
                        ">
                            <option value="">Select a reason...</option>
                            <option value="products_shortage">Products Shortage/Unavailability</option>
                            <option value="order_expired">Order has been received 24+ hours ago</option>
                            <option value="customer_cannot_afford">Customer can't afford the order</option>
                            <option value="customer_request">Customer requested cancellation</option>
                            <option value="pharmacy_error">Pharmacy processing error</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="additionalMessage" style="
                            display: block;
                            margin-bottom: 8px;
                            font-weight: 600;
                            color: #333;
                        ">Additional Message <span style="color: #666; font-weight: normal;">(Optional)</span></label>
                        <textarea id="additionalMessage" rows="4" placeholder="Provide additional details about the cancellation..." style="
                            width: 100%;
                            padding: 10px;
                            border: 2px solid #ddd;
                            border-radius: 4px;
                            font-size: 14px;
                            resize: vertical;
                            font-family: inherit;
                        "></textarea>
                    </div>

                    <div class="cancellation-warning" style="
                        background: #fff3e0;
                        border: 1px solid #ffcc02;
                        border-radius: 4px;
                        padding: 12px;
                        margin-bottom: 20px;
                    ">
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span style="color: #f57c00; margin-right: 8px; font-size: 18px;">⚠️</span>
                            <strong style="color: #e65100;">Warning</strong>
                        </div>
                        <p style="margin: 0; color: #bf360c; font-size: 14px;">
                            This action cannot be undone. The order will be permanently cancelled and the customer will be notified.
                        </p>
                    </div>
                </div>

                <div class="modal-footer" style="
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    padding: 20px;
                    border-top: 1px solid #eee;
                    background: #f9f9f9;
                ">
                    <button class="btn btn-secondary cancel-order-cancel" style="
                        padding: 10px 20px;
                        border: 2px solid #666;
                        background: white;
                        color: #666;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: 500;
                    ">Cancel</button>
                    <button class="btn btn-danger confirm-cancel-order" style="
                        padding: 10px 20px;
                        border: 2px solid #d32f2f;
                        background: #d32f2f;
                        color: white;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: 500;
                    ">Confirm Cancellation</button>
                </div>
            </div>
        `;

                // Bind events for the modal
                this.bindCancelModalEvents(modal);

                return modal;
            }

            bindCancelModalEvents(modal) {
                // Close modal events
                const closeBtn = modal.querySelector('.cancel-modal-close');
                const cancelBtn = modal.querySelector('.cancel-order-cancel');

                const closeModal = () => {
                    modal.style.display = 'none';
                };

                if (closeBtn) closeBtn.addEventListener('click', closeModal);
                if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

                // Click outside to close
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeModal();
                    }
                });

                // Confirm cancellation
                const confirmBtn = modal.querySelector('.confirm-cancel-order');
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', () => this.processCancelOrder(modal));
                }

                // Escape key to close
                modal.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });

                // Form validation
                const reasonSelect = modal.querySelector('#cancelReason');
                if (reasonSelect) {
                    reasonSelect.addEventListener('change', () => {
                        this.validateCancelForm(modal);
                    });
                }
            }

            validateCancelForm(modal) {
                const reasonSelect = modal.querySelector('#cancelReason');
                const confirmBtn = modal.querySelector('.confirm-cancel-order');

                const isValid = reasonSelect?.value?.trim() !== '';

                if (confirmBtn) {
                    confirmBtn.disabled = !isValid;
                    confirmBtn.style.opacity = isValid ? '1' : '0.6';
                    confirmBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
                }

                return isValid;
            }

            async processCancelOrder(modal) {
                if (!this.validateCancelForm(modal)) {
                    alert('Please select a reason for cancellation.');
                    return;
                }

                if (this.state.isLoading) return;

                const prescriptionId = modal.dataset.prescriptionId;
                const reasonSelect = modal.querySelector('#cancelReason');
                const additionalMessage = modal.querySelector('#additionalMessage');

                const reason = reasonSelect.value;
                const message = additionalMessage.value.trim();

                // Get readable reason text
                const reasonText = this.getCancellationReasonText(reason);

                // Final confirmation
                const confirmationMessage =
                    `Are you sure you want to cancel this order?\n\nReason: ${reasonText}${message ? `\nAdditional details: ${message}` : ''}`;

                if (!confirm(confirmationMessage)) {
                    return;
                }

                this.state.isLoading = true;
                const confirmBtn = modal.querySelector('.confirm-cancel-order');
                const originalText = confirmBtn?.textContent || 'Confirm Cancellation';

                if (confirmBtn) {
                    confirmBtn.textContent = 'Cancelling...';
                    confirmBtn.disabled = true;
                }

                try {
                    const response = await fetch(`/admin/orders/${prescriptionId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            reason: reason,
                            message: message || null
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || `Server error: ${response.status}`);
                    }

                    if (data.success) {
                        this.showSuccessMessage(data.message || 'Order cancelled successfully!');

                        // Close modal
                        modal.style.display = 'none';

                        // Update UI instead of full page reload
                        this.updateOrderRowStatus(prescriptionId, 'cancelled');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(data.message || 'Failed to cancel order');
                    }

                } catch (error) {
                    console.error('Error cancelling order:', error);
                    alert('Failed to cancel order: ' + error.message);
                } finally {
                    this.state.isLoading = false;
                    if (confirmBtn) {
                        confirmBtn.textContent = originalText;
                        confirmBtn.disabled = false;
                    }
                }
            }

            getCancellationReasonText(reason) {
                const reasonMap = {
                    'products_shortage': 'Products Shortage/Unavailability.',
                    'order_expired': 'Order has been received 24+ hours ago.',
                    'customer_cannot_afford': 'Customer can\'t afford the order.',
                    'customer_request': 'Customer requested cancellation.',
                    'pharmacy_error': 'Pharmacy processing error.',
                    'other': 'Other.'
                };

                return reasonMap[reason] || reason;
            }

            updateOrderRowStatus(prescriptionId, newStatus) {
                const orderRow = document.querySelector(`[data-id="${prescriptionId}"]`)?.closest('.order-row');
                if (orderRow) {
                    orderRow.dataset.status = newStatus;
                    const statusElement = orderRow.querySelector('.status-badges');
                    if (statusElement) {
                        statusElement.className = `status-badges ${newStatus}`;
                        statusElement.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    }
                }
            }

            showSuccessMessage(message) {
                // Create temporary success notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success';
                notification.style.cssText =
                    'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px;';
                notification.textContent = message;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            // Enhanced Product Management
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

            // OPTIMIZED SEARCH - Enhanced search with better performance
            handleProductSearch() {
                const searchTerm = this.elements.productSearchInput.value.toLowerCase().trim();
                const selectedProductIds = new Set(
                    (this.state.selectedProductsByOrder[this.state.currentPrescriptionId] || []).map(p => p.id
                        .toString())
                );

                let visibleCount = 0;
                const productItems = document.querySelectorAll('#availableProducts .product-item');

                // Use requestAnimationFrame for smooth UI updates
                requestAnimationFrame(() => {
                    productItems.forEach(li => {
                        const productName = (li.dataset.name || '').toLowerCase();
                        const isSelected = selectedProductIds.has(li.dataset.id);

                        const matchesSearch = !searchTerm || productName.includes(searchTerm);
                        const shouldShow = matchesSearch && !isSelected;

                        li.style.display = shouldShow ? 'block' : 'none';
                        if (shouldShow) visibleCount++;
                    });

                    this.showProductSearchResults(searchTerm, visibleCount);
                });
            }

            // Enhanced search results display
            showProductSearchResults(searchTerm, visibleCount) {
                let noResultsMsg = document.querySelector('.no-search-results');

                if (searchTerm && visibleCount === 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('li');
                        noResultsMsg.className = 'no-search-results';
                        noResultsMsg.style.cssText = `
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-style: italic;
                    border: 2px dashed #ddd;
                    margin: 10px 0;
                    border-radius: 8px;
                `;
                        noResultsMsg.innerHTML = `
                    <div>No products found for "<strong>${searchTerm}</strong>"</div>
                    <small>Try a different search term</small>
                `;
                        this.elements.availableList.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }

                // Update search results counter
                this.updateSearchCounter(searchTerm, visibleCount);
            }

            // Add search counter for better UX
            updateSearchCounter(searchTerm, visibleCount) {
                let counter = document.querySelector('.search-results-counter');

                if (!counter) {
                    counter = document.createElement('div');
                    counter.className = 'search-results-counter';
                    counter.style.cssText = `
                font-size: 0.85em;
                color: #666;
                margin: 5px 0;
                text-align: center;
            `;

                    const searchContainer = this.elements.productSearchInput.parentNode;
                    if (searchContainer) {
                        searchContainer.appendChild(counter);
                    }
                }

                if (searchTerm) {
                    counter.textContent = `Found ${visibleCount} product${visibleCount !== 1 ? 's' : ''}`;
                    counter.style.display = 'block';
                } else {
                    counter.style.display = 'none';
                }
            }

            removeSelectedProduct(productId) {
                if (this.state.selectedProductsByOrder[this.state.currentPrescriptionId]) {
                    this.state.selectedProductsByOrder[this.state.currentPrescriptionId] =
                        this.state.selectedProductsByOrder[this.state.currentPrescriptionId]
                        .filter(p => p.id.toString() !== productId.toString());

                    this.updateSelectedProductsDisplay();
                }
            }

            closeQtyModal() {
                if (this.elements.qtyModal) {
                    this.elements.qtyModal.style.display = 'none';
                }
                this.state.selectedProductLi = null;
            }

            increaseQuantity() {
                if (!this.state.selectedProductLi || !this.elements.qtyInput) return;

                const currentQty = parseInt(this.elements.qtyInput.value) || 1;
                const maxStock = parseInt(this.state.selectedProductLi.dataset.stock) || 0;

                if (currentQty < maxStock) {
                    this.elements.qtyInput.value = currentQty + 1;
                } else {
                    this.showStockLimitMessage(maxStock);
                }
            }

            decreaseQuantity() {
                if (!this.elements.qtyInput) return;

                const currentQty = parseInt(this.elements.qtyInput.value) || 1;
                if (currentQty > 1) {
                    this.elements.qtyInput.value = currentQty - 1;
                }
            }

            validateQuantityInput() {
                if (!this.elements.qtyInput || !this.state.selectedProductLi) return;

                const currentQty = parseInt(this.elements.qtyInput.value) || 1;
                const maxStock = parseInt(this.state.selectedProductLi.dataset.stock || 999);

                if (currentQty < 1) {
                    this.elements.qtyInput.value = 1;
                } else if (currentQty > maxStock) {
                    this.elements.qtyInput.value = maxStock;
                    this.showStockLimitMessage(maxStock);
                }
            }

            showStockLimitMessage(maxStock) {
                const message = `Maximum stock available: ${maxStock}`;

                // Show temporary message instead of alert
                let notification = document.querySelector('.stock-limit-notification');
                if (notification) {
                    notification.remove();
                }

                notification = document.createElement('div');
                notification.className = 'stock-limit-notification';
                notification.style.cssText = `
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff9800;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
        `;
                notification.textContent = message;

                const qtyControls = document.querySelector('.quantity-controls');
                if (qtyControls) {
                    qtyControls.style.position = 'relative';
                    qtyControls.appendChild(notification);

                    setTimeout(() => notification?.remove(), 2000);
                }
            }

            confirmQuantitySelection() {
                if (!this.state.selectedProductLi || !this.elements.qtyInput) return;

                const quantity = parseInt(this.elements.qtyInput.value) || 1;
                const productData = {
                    id: this.state.selectedProductLi.dataset.id,
                    name: this.state.selectedProductLi.dataset.product,
                    price: this.state.selectedProductLi.dataset.price,
                    quantity: quantity
                };

                const maxStock = parseInt(this.state.selectedProductLi.dataset.stock) || 0;

                if (quantity > maxStock) {
                    alert(`Cannot select ${quantity} items. Only ${maxStock} available in stock.`);
                    return;
                }

                if (!this.state.selectedProductsByOrder[this.state.currentPrescriptionId]) {
                    this.state.selectedProductsByOrder[this.state.currentPrescriptionId] = [];
                }

                const existingProduct = this.state.selectedProductsByOrder[this.state.currentPrescriptionId]
                    .find(p => p.id === productData.id);

                if (existingProduct) {
                    if (confirm(`"${productData.name}" is already selected. Update quantity to ${quantity}?`)) {
                        existingProduct.quantity = quantity;
                        this.updateSelectedProductsDisplay();
                    }
                } else {
                    this.state.selectedProductsByOrder[this.state.currentPrescriptionId].push(productData);
                    this.updateSelectedProductsDisplay();
                }

                this.closeQtyModal();
            }

            async loadAvailableProducts(showLoading = true) {
                const availableList = this.elements.availableList;
                if (!availableList) return;

                // Use cache if available and recent
                if (this.state.cache.products &&
                    Date.now() - this.state.cache.lastProductFetch < this.CACHE_DURATION) {
                    this.renderProducts(this.state.cache.products);
                    return;
                }

                if (showLoading) {
                    availableList.innerHTML = '<li class="loading-item">Loading products...</li>';
                }

                try {
                    const response = await fetch('/admin/products', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.getCSRFToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    const products = data.success && data.products ? data.products : data.data || data || [];

                    // Cache the products
                    this.state.cache.products = products;
                    this.state.cache.lastProductFetch = Date.now();

                    this.renderProducts(products);

                } catch (error) {
                    console.error('Error loading products:', error);
                    availableList.innerHTML = this.createErrorMessage(error.message);
                }
            }

            renderProducts(products) {
                const availableList = this.elements.availableList;
                if (!availableList) return;

                availableList.innerHTML = '';

                if (products && products.length > 0) {
                    const fragment = document.createDocumentFragment();

                    products.forEach(product => {
                        const li = this.createProductListItem(product);
                        fragment.appendChild(li);
                    });

                    availableList.appendChild(fragment);
                } else {
                    availableList.innerHTML = '<li class="no-products">No products available</li>';
                }
            }

            createErrorMessage(errorMessage) {
                return `
            <li class="error-item">
                <div style="text-align: center; padding: 20px; color: #d32f2f;">
                    <div>Error loading products: ${errorMessage}</div>
                    <button onclick="orderManager.loadAvailableProducts()"
                            style="margin-top: 10px; padding: 5px 15px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Retry
                    </button>
                </div>
            </li>
        `;
            }

            // OPTIMIZED PRODUCT LIST CREATION - with better event handling
            createProductListItem(product) {
                const li = document.createElement('li');
                li.className = 'product-item';
                li.setAttribute('tabindex', '0'); // Make focusable for keyboard navigation
                li.setAttribute('role', 'button');
                li.setAttribute('aria-label', `Select ${product.product_name || product.name}`);

                // Store data efficiently
                Object.assign(li.dataset, {
                    id: product.id,
                    product: product.name || product.product_name,
                    name: (product.name || product.product_name || '').toLowerCase(),
                    price: parseFloat(product.price || product.sale_price || product.selling_price || 0)
                        .toFixed(2),
                    stock: this.getTotalQuantity(product).toString()
                });

                const stockClass = this.getStockClass(product);
                li.classList.add(stockClass);

                // Use template for consistent structure
                li.innerHTML = this.getProductItemTemplate(product, li.dataset.price, stockClass);

                return li;
            }

            // Template method for consistent HTML structure
            getProductItemTemplate(product, price, stockClass) {
                return `
            <div class="product-main">
                <span class="product-name">${product.product_name || product.name}</span>
                <span class="product-price">₱${price}</span>
            </div>
            <div class="product-details">
                ${product.form_type ? `<span class="product-form">${product.form_type}</span>` : ''}
                ${product.dosage_unit ? `<span class="product-dosage">${product.dosage_unit}</span>` : ''}
                ${product.manufacturer ? `<span class="manufacturer">${product.manufacturer}</span>` : ''}
            </div>
            <div class="product-stock ${stockClass}">${this.getStockText(product)}</div>
            ${stockClass === 'out-of-stock' ? '<div class="stock-overlay">Out of Stock</div>' : ''}
        `;
            }

            getTotalQuantity(product) {
                if (product.batches && Array.isArray(product.batches)) {
                    return product.batches
                        .filter(batch => batch.quantity_remaining > 0)
                        .reduce((total, batch) => total + parseInt(batch.quantity_remaining || 0), 0);
                }
                return parseInt(product.stock || product.quantity || product.total_stock || 0);
            }

            getStockClass(product) {
                const totalQuantity = this.getTotalQuantity(product);
                const reorderLevel = parseInt(product.reorder_level || 10);

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
                return totalQuantity === 0 ? 'Out of Stock' : `${totalQuantity} available`;
            }

            updateSelectedProductsDisplay() {
                if (!this.elements.selectedList || !this.state.currentPrescriptionId) return;

                const selectedProducts = this.state.selectedProductsByOrder[this.state.currentPrescriptionId] || [];

                // Clear current display
                this.elements.selectedList.innerHTML = '';

                // Reset search and show all available products
                if (this.elements.productSearchInput) {
                    this.elements.productSearchInput.value = '';
                }

                // Create selected products list
                if (selectedProducts.length > 0) {
                    const fragment = document.createDocumentFragment();

                    selectedProducts.forEach(product => {
                        const li = this.createSelectedProductItem(product);
                        fragment.appendChild(li);
                    });

                    this.elements.selectedList.appendChild(fragment);
                }

                // Update available products visibility
                this.updateAvailableProductsVisibility(selectedProducts);

                // Update totals display
                this.updateSelectionTotals(selectedProducts);
            }

            createSelectedProductItem(product) {
                const li = document.createElement('li');
                li.dataset.id = product.id.toString();
                li.className = 'selected-product-item';

                const totalPrice = (parseFloat(product.price) * parseInt(product.quantity)).toFixed(2);

                li.innerHTML = `
            <div class="selected-product-main">
                <span class="selected-product-name">${product.name}</span>
                <span class="selected-product-quantity">Qty: ${product.quantity}</span>
            </div>
            <div class="selected-product-price">₱${product.price} each | Total: ₱${totalPrice}</div>
            <button class="remove-btn" data-id="${product.id}" type="button" title="Remove product">
                ❌
            </button>
        `;

                return li;
            }

            updateAvailableProductsVisibility(selectedProducts) {
                const selectedIds = new Set(selectedProducts.map(p => p.id.toString()));

                document.querySelectorAll('#availableProducts li').forEach(li => {
                    const isSelected = selectedIds.has(li.dataset.id);
                    li.style.display = isSelected ? 'none' : 'block';
                });
            }

            updateSelectionTotals(selectedProducts) {
                let totalItems = 0;
                let totalAmount = 0;

                selectedProducts.forEach(product => {
                    totalItems += parseInt(product.quantity);
                    totalAmount += parseFloat(product.price) * parseInt(product.quantity);
                });

                // Update or create totals display
                let totalsDisplay = document.querySelector('.selection-totals');
                if (!totalsDisplay) {
                    totalsDisplay = document.createElement('div');
                    totalsDisplay.className = 'selection-totals';
                    totalsDisplay.style.cssText =
                        'margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; font-weight: bold;';

                    const selectedSection = document.querySelector('.selected-products-section');
                    if (selectedSection) {
                        selectedSection.appendChild(totalsDisplay);
                    }
                }

                if (selectedProducts.length > 0) {
                    totalsDisplay.innerHTML = `
                <div>Total Items: ${totalItems}</div>
                <div>Total Amount: ₱${totalAmount.toFixed(2)}</div>
            `;
                    totalsDisplay.style.display = 'block';
                } else {
                    totalsDisplay.style.display = 'none';
                }
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
                        if (data.success && data.items && Array.isArray(data.items)) {
                            this.state.selectedProductsByOrder[prescriptionId] = data.items.map(item => ({
                                id: item.product_id || item.id,
                                name: item.product_name || item.name,
                                price: parseFloat(item.unit_price || item.price || 0).toFixed(2),
                                quantity: parseInt(item.quantity || 1)
                            }));
                        }
                    }
                } catch (error) {
                    console.warn('No saved products found or error loading:', error.message);
                    this.state.selectedProductsByOrder[prescriptionId] = [];
                }

                this.updateSelectedProductsDisplay();
            }

            async saveAndCompleteOrder(e) {
                e.preventDefault();

                if (this.state.isLoading) return;

                const selectedProducts = this.state.selectedProductsByOrder[this.state.currentPrescriptionId] || [];

                if (!selectedProducts.length) {
                    alert("Please select at least one product before completing the order.");
                    return;
                }

                // Validate stock levels before proceeding
                const stockValidation = await this.validateProductStock(selectedProducts);
                if (!stockValidation.valid) {
                    alert(`Stock validation failed:\n${stockValidation.errors.join('\n')}`);
                    return;
                }

                const orderSummary = this.generateOrderSummary(selectedProducts);
                if (!confirm(`Complete this order?\n\n${orderSummary}`)) {
                    return;
                }

                this.state.isLoading = true;
                const saveBtn = document.getElementById('saveSelection');
                const originalText = saveBtn?.textContent || 'Save & Complete Order';

                if (saveBtn) {
                    saveBtn.textContent = 'Processing...';
                    saveBtn.disabled = true;
                }

                try {
                    // Save products first
                    await this.saveOrderProducts(selectedProducts);

                    // Then complete the order
                    const completionResult = await this.completeOrder();

                    this.handleOrderCompletion(completionResult);

                } catch (error) {
                    console.error('Error processing order:', error);
                    alert('Failed to process order: ' + error.message);
                } finally {
                    this.state.isLoading = false;
                    if (saveBtn) {
                        saveBtn.textContent = originalText;
                        saveBtn.disabled = false;
                    }
                }
            }

            async validateProductStock(selectedProducts) {
                const validation = {
                    valid: true,
                    errors: []
                };

                for (const product of selectedProducts) {
                    try {
                        const response = await fetch(`/admin/products/${product.id}/stock`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCSRFToken()
                            }
                        });

                        if (response.ok) {
                            const stockData = await response.json();
                            const availableStock = stockData.available_stock || 0;

                            if (product.quantity > availableStock) {
                                validation.valid = false;
                                validation.errors.push(
                                    `${product.name}: Requested ${product.quantity}, but only ${availableStock} available`
                                );
                            }
                        }
                    } catch (error) {
                        console.warn(`Could not validate stock for ${product.name}:`, error);
                    }
                }

                return validation;
            }

            generateOrderSummary(selectedProducts) {
                let totalItems = 0;
                let totalAmount = 0;

                const itemsList = selectedProducts.map(product => {
                    const itemTotal = parseFloat(product.price) * parseInt(product.quantity);
                    totalItems += parseInt(product.quantity);
                    totalAmount += itemTotal;

                    return `• ${product.name} (Qty: ${product.quantity}) - ₱${itemTotal.toFixed(2)}`;
                }).join('\n');

                return `Order Summary:\n${itemsList}\n\nTotal Items: ${totalItems}\nTotal Amount: ₱${totalAmount.toFixed(2)}`;
            }

            async saveOrderProducts(selectedProducts) {
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
                    throw new Error(`Failed to save products: ${response.status}`);
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save products');
                }

                return data;
            }

            async completeOrder() {
                const response = await fetch(`/orders/${this.state.currentPrescriptionId}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCSRFToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_method: 'cash',
                        notes: 'Order processed and completed via admin panel'
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || `Failed to complete order: ${response.status}`);
                }

                if (!data.success) {
                    throw new Error(data.message || 'Failed to complete order');
                }

                return data;
            }

            handleOrderCompletion(completionResult) {
                const message = `Order completed successfully!\n\n` +
                    `Sale ID: ${completionResult.sale_id}\n` +
                    `Total Amount: ₱${parseFloat(completionResult.total_amount).toFixed(2)}\n` +
                    `Total Items: ${completionResult.total_items}`;

                alert(message);
                this.closeManageOrderModal();

                // Update UI instead of full reload
                this.updateOrderRowStatus(this.state.currentPrescriptionId, 'completed');

                // Refresh cache
                this.state.cache.products = null;

                setTimeout(() => window.location.reload(), 2000);
            }

            // Prescription Viewer Management
            resetPrescriptionViewer() {
                const elements = {
                    container: document.getElementById('prescriptionReferenceContainer'),
                    toggleBtn: document.getElementById('togglePrescriptionBtn'),
                    refreshBtn: document.getElementById('refreshPrescriptionBtn'),
                    contentRef: document.getElementById('prescriptionContentRef'),
                    loadingRef: document.getElementById('prescriptionLoadingRef'),
                    errorRef: document.getElementById('prescriptionErrorRef')
                };

                if (elements.container) elements.container.style.display = 'none';
                if (elements.toggleBtn) elements.toggleBtn.textContent = 'Show Prescription';
                if (elements.refreshBtn) elements.refreshBtn.style.display = 'none';
                if (elements.loadingRef) elements.loadingRef.style.display = 'none';
                if (elements.errorRef) elements.errorRef.style.display = 'none';

                if (elements.contentRef) {
                    elements.contentRef.innerHTML =
                        '<div class="no-prescription-message"><p>No prescription document available</p></div>';
                }
            }

            togglePrescriptionViewer() {
                const container = document.getElementById('prescriptionReferenceContainer');
                const toggleBtn = document.getElementById('togglePrescriptionBtn');
                const refreshBtn = document.getElementById('refreshPrescriptionBtn');

                if (!container || !toggleBtn) return;

                const isVisible = container.style.display !== 'none';

                if (!isVisible) {
                    if (this.state.currentManageOrderData.hasDocument) {
                        container.style.display = 'block';
                        toggleBtn.textContent = 'Hide Prescription';
                        if (refreshBtn) refreshBtn.style.display = 'inline-block';
                        this.loadPrescriptionReference();
                    } else {
                        container.style.display = 'block';
                        toggleBtn.textContent = 'Hide Prescription';
                        this.showNoPrescriptionMessage();
                    }
                } else {
                    container.style.display = 'none';
                    toggleBtn.textContent = 'Show Prescription';
                    if (refreshBtn) refreshBtn.style.display = 'none';
                }
            }

            showNoPrescriptionMessage() {
                const contentRef = document.getElementById('prescriptionContentRef');
                if (contentRef) {
                    contentRef.innerHTML = `
                <div class="no-prescription-message">
                    <p>No prescription document available for this order.</p>
                    <p><small>This may be a direct medicine order that doesn't require prescription validation.</small></p>
                </div>
            `;
                }
            }

            async loadPrescriptionReference() {
                if (!this.state.currentManageOrderData.hasDocument) {
                    this.showNoPrescriptionMessage();
                    return;
                }

                const elements = {
                    contentRef: document.getElementById('prescriptionContentRef'),
                    loadingRef: document.getElementById('prescriptionLoadingRef'),
                    errorRef: document.getElementById('prescriptionErrorRef')
                };

                // Show loading state
                if (elements.loadingRef) elements.loadingRef.style.display = 'block';
                if (elements.errorRef) elements.errorRef.style.display = 'none';
                if (elements.contentRef) elements.contentRef.style.display = 'none';

                try {
                    const {
                        documentType,
                        documentUrl,
                        filename
                    } = this.state.currentManageOrderData;

                    await new Promise((resolve, reject) => {
                        if (documentType === 'image' || documentType === 'legacy') {
                            this.loadImagePrescription(documentUrl, elements, resolve, reject);
                        } else if (documentType === 'pdf') {
                            this.loadPDFPrescription(documentUrl, elements, resolve, reject);
                        } else {
                            reject(new Error('Unsupported document type'));
                        }
                    });

                } catch (error) {
                    console.error('Error loading prescription:', error);
                    this.showPrescriptionError(elements, error.message);
                }
            }

            loadImagePrescription(documentUrl, elements, resolve, reject) {
                const img = new Image();

                img.onload = () => {
                    if (elements.contentRef) {
                        elements.contentRef.innerHTML = `
                    <div class="prescription-image-container">
                        <img src="${documentUrl}" alt="Prescription Document"
                             style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" />
                    </div>
                `;
                        elements.contentRef.style.display = 'block';
                    }
                    if (elements.loadingRef) elements.loadingRef.style.display = 'none';
                    resolve();
                };

                img.onerror = () => {
                    reject(new Error('Failed to load prescription image'));
                };

                // Set timeout for loading
                setTimeout(() => reject(new Error('Image loading timeout')), 10000);

                img.src = documentUrl;
            }

            loadPDFPrescription(documentUrl, elements, resolve, reject) {
                if (elements.contentRef) {
                    elements.contentRef.innerHTML = `
                <div class="prescription-pdf-container">
                    <iframe src="${documentUrl}"
                            title="Prescription PDF"
                            style="width: 100%; height: 500px; border: none; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                            onload="this.style.display='block'"
                            onerror="this.style.display='none'">
                    </iframe>
                    <div style="text-align: center; margin-top: 10px;">
                        <a href="${documentUrl}" target="_blank" class="btn btn-outline">
                            Open PDF in New Tab
                        </a>
                    </div>
                </div>
            `;
                    elements.contentRef.style.display = 'block';
                }
                if (elements.loadingRef) elements.loadingRef.style.display = 'none';

                // PDF iframe doesn't have reliable load events, so resolve immediately
                setTimeout(resolve, 1000);
            }

            showPrescriptionError(elements, errorMessage) {
                if (elements.loadingRef) elements.loadingRef.style.display = 'none';
                if (elements.contentRef) elements.contentRef.style.display = 'none';

                if (elements.errorRef) {
                    elements.errorRef.innerHTML = `
                <p>Error loading prescription: ${errorMessage}</p>
                <button class="retry-btn" onclick="orderManager.loadPrescriptionReference()">
                    Retry Loading
                </button>
            `;
                    elements.errorRef.style.display = 'block';
                }
            }

            // Full Modal Prescription Viewer
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
                const contentDiv = document.getElementById('prescriptionContent');
                const loadingDiv = document.getElementById('prescriptionLoading');
                const errorDiv = document.getElementById('prescriptionError');

                if (!modal) return;

                // Set modal title and show modal
                if (orderIdSpan) orderIdSpan.textContent = filename || `Order ${id}`;
                modal.style.display = 'flex';
                modal.classList.add('active');

                // Show loading state
                if (loadingDiv) loadingDiv.style.display = 'block';
                if (contentDiv) contentDiv.style.display = 'none';
                if (errorDiv) errorDiv.style.display = 'none';

                // Load content based on type
                if (type === 'image' || type === 'legacy') {
                    this.loadModalImageContent(viewUrl, contentDiv, loadingDiv, errorDiv);
                } else if (type === 'pdf') {
                    this.loadModalPDFContent(viewUrl, contentDiv, loadingDiv, errorDiv);
                } else {
                    this.showModalError(errorDiv, loadingDiv, contentDiv, 'Unsupported file type');
                }
            }

            loadModalImageContent(viewUrl, contentDiv, loadingDiv, errorDiv) {
                const img = new Image();

                img.onload = () => {
                    if (contentDiv) {
                        contentDiv.innerHTML = `
                    <div class="modal-prescription-image">
                        <img src="${viewUrl}" alt="Prescription"
                             style="max-width: 100%; max-height: 70vh; object-fit: contain;" />
                    </div>
                `;
                        contentDiv.style.display = 'block';
                    }
                    if (loadingDiv) loadingDiv.style.display = 'none';
                };

                img.onerror = () => {
                    this.showModalError(errorDiv, loadingDiv, contentDiv, 'Failed to load image');
                };

                img.src = viewUrl;
            }

            loadModalPDFContent(viewUrl, contentDiv, loadingDiv, errorDiv) {
                if (contentDiv) {
                    contentDiv.innerHTML = `
                <div class="modal-prescription-pdf">
                    <iframe src="${viewUrl}"
                            style="width: 100%; height: 70vh; border: none;"
                            title="Prescription PDF">
                    </iframe>
                </div>
            `;
                    contentDiv.style.display = 'block';
                }
                if (loadingDiv) loadingDiv.style.display = 'none';
            }

            showModalError(errorDiv, loadingDiv, contentDiv, message) {
                if (loadingDiv) loadingDiv.style.display = 'none';
                if (contentDiv) contentDiv.style.display = 'none';
                if (errorDiv) {
                    errorDiv.innerHTML = `<p>${message}</p>`;
                    errorDiv.style.display = 'block';
                }
            }

            closePrescriptionViewer() {
                if (this.elements.prescriptionModal) {
                    this.elements.prescriptionModal.style.display = 'none';
                    this.elements.prescriptionModal.classList.remove('active');
                }
                this.state.currentPrescriptionData = null;
            }
        }

        // Initialize the system when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            window.orderManager = new OrderManagementSystem();
        });

        // Export for module systems if needed
        if (typeof module !== 'undefined' && module.exports) {
            module.exports = OrderManagementSystem;
        }

        // Also support AMD/RequireJS
        if (typeof define === 'function' && define.amd) {
            define(function() {
                return OrderManagementSystem;
            });
        }
    </script>
    @stack('scripts')
</body>

</html>
