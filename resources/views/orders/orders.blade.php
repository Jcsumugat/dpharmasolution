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
                <div class="stat-label">Medicine Orders</div>
            </div>
        </div>

        <div class="search-container">
            <input type="text" class="search-input" id="order-search"
                placeholder="Search by Order ID, Customer, Mobile, or Notes...">
            <select class="filter-btn" id="status-filter">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="completed">Completed</option>
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
                            </td>

                            <td>
                                <div>{{ $prescription->mobile_number }}</div>
                                @if ($prescription->customer && $prescription->customer->address)
                                    <div><small>{{ Str::limit($prescription->customer->address, 30) }}</small></div>
                                @endif
                            </td>

                            <td>
                                <span class="status-badge {{ strtolower($prescription->status ?? 'pending') }}">
                                    {{ ucfirst($prescription->status ?? 'Pending') }}
                                </span>
                                @if ($prescription->notes)
                                    <div class="order-meta">
                                        <strong>Notes:</strong> {{ Str::limit($prescription->notes, 50) }}
                                    </div>
                                @endif
                                @if ($prescription->order_type === 'online_order')
                                    <div class="order-meta">
                                        Direct medicine order - no prescription validation required
                                    </div>
                                @else
                                    <div class="order-meta">
                                        Requires pharmacist prescription review
                                    </div>
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
                                                    target="_blank" class="btn-file btn-view">
                                                    View Image
                                                </a>
                                            @endif
                                            <a href="{{ route('prescription.file.download', $prescription->id) }}"
                                                class="btn-file btn-download">
                                                Download
                                            </a>
                                        </div>
                                        <div style="font-size: 0.75em; color: #6c757d; margin-top: 2px;">
                                            Type: {{ $prescription->file_mime_type ?? 'Unknown' }}
                                        </div>
                                    </div>
                                @elseif($prescription->file_path)
                                    <div class="prescription-file-info legacy-file">
                                        <strong>Legacy File</strong>
                                        <span
                                            style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold;">
                                            UNENCRYPTED
                                        </span>
                                        <div class="file-actions">
                                            <a href="{{ asset('storage/' . $prescription->file_path) }}"
                                                target="_blank" class="btn-file btn-view">
                                                View File
                                            </a>
                                        </div>
                                        <div style="font-size: 0.75em; color: #856404; margin-top: 2px;">
                                            Uploaded before encryption
                                        </div>
                                    </div>
                                @else
                                    <span class="no-file">No document</span>
                                @endif
                            </td>

                            <td>{{ $prescription->created_at->format('M d, Y') }}<br><small>{{ $prescription->created_at->format('H:i') }}</small>
                            </td>

                            <td class="action-cell">
                                <div class="action-dropdown">
                                    <button class="dropdown-trigger" data-id="{{ $prescription->id }}">&#8230;</button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item manage manage-order-btn"
                                            data-id="{{ $prescription->id }}">
                                            Manage Products
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        @if ($prescription->order_type === 'prescription')
                                            <button class="dropdown-item approve action-btn" data-action="approve"
                                                data-id="{{ $prescription->id }}">
                                                Approve Prescription
                                            </button>
                                        @else
                                            <button class="dropdown-item approve action-btn" data-action="approve"
                                                data-id="{{ $prescription->id }}">
                                                Approve Order
                                            </button>
                                        @endif
                                        <button class="dropdown-item cancel action-btn" data-action="cancel"
                                            data-id="{{ $prescription->id }}">
                                            Cancel Order
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item complete complete-order-btn"
                                            data-id="{{ $prescription->id }}">
                                            Complete Order
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="dropdown-overlay"></div>

        <div id="messageModal" class="modal">
            <div class="modal-content">
                <h3 id="modalTitle">Send a Message</h3>
                <form id="actionForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="prescriptionId" />
                    <input type="hidden" name="action" id="actionType" />

                    <label for="messageSelect">Choose a message:</label>
                    <select id="messageSelect" name="message" class="dropdown" required>
                        <option value="">-- Select a message --</option>
                    </select>

                    <label for="customMessage">Additional notes (optional):</label>
                    <textarea id="customMessage" name="custom_message" rows="3"
                        placeholder="Add any additional notes for the customer..."></textarea>

                    <div class="modal-actions">
                        <button type="button" id="cancelModal" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send</button>
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
                                    ₱{{ number_format($salePrice, 2) }}
                                    | Stock: {{ $totalStock }}
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            let currentOrderPrescriptionId = null;

            const messages = {
                approve: [
                    'Your order has been approved and is ready for pickup.',
                    'Prescription approved by pharmacist. Your order is being prepared.'
                ],
                cancel: [
                    'Your order has been cancelled due to stock shortages/unavailability.',
                    'Order cancelled - some medications are currently out of stock.',
                    'Unable to fulfill your order at this time due to product unavailability.',
                    'Order cancelled due to prescription issues. Please contact us for assistance.'
                ]
            };

            const dropdownOverlay = document.querySelector('.dropdown-overlay');

            function getCSRFToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            }

            const searchInput = document.getElementById('order-search');
            const statusFilter = document.getElementById('status-filter');
            const typeFilters = document.querySelectorAll('.filter-btn[data-filter]');

            function filterOrders() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusFilter = document.getElementById('status-filter').value;
                const activeTypeFilter = document.querySelector('.filter-btn.active').dataset.filter;

                document.querySelectorAll('.order-row').forEach(row => {
                    const searchData = row.dataset.search || '';
                    const orderStatus = row.dataset.status;
                    const orderType = row.dataset.orderType;

                    let show = true;

                    if (searchTerm && !searchData.includes(searchTerm)) {
                        show = false;
                    }

                    if (statusFilter !== 'all' && orderStatus !== statusFilter) {
                        show = false;
                    }

                    if (activeTypeFilter !== 'all' && orderType !== activeTypeFilter) {
                        show = false;
                    }

                    row.style.display = show ? '' : 'none';
                });
            }

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

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('dropdown-trigger')) {
                    e.stopPropagation();

                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });

                    const dropdown = e.target.nextElementSibling;
                    dropdown.classList.toggle('show');

                    if (dropdown.classList.contains('show')) {
                        dropdownOverlay.style.display = 'block';
                    }
                }
            });

            function closeAllDropdowns() {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                dropdownOverlay.style.display = 'none';
            }

            dropdownOverlay.addEventListener('click', closeAllDropdowns);
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.action-dropdown')) {
                    closeAllDropdowns();
                }
            });

            const modal = document.getElementById('messageModal');
            const dropdown = document.getElementById('messageSelect');
            const form = document.getElementById('actionForm');
            const prescriptionIdInput = document.getElementById('prescriptionId');
            const actionTypeInput = document.getElementById('actionType');
            const modalTitle = document.getElementById('modalTitle');

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('action-btn')) {
                    closeAllDropdowns();
                    const action = e.target.dataset.action;
                    const id = e.target.dataset.id;

                    let message = '';
                    let confirmText = '';

                    if (action === 'approve') {
                        message = 'Your order will be completed at anytime.';
                        confirmText = 'Are you sure you want to approve this order?';
                    } else if (action === 'cancel') {
                        message = 'Your Order has been cancelled due to stock shortages/unavailability.';
                        confirmText = 'Are you sure you want to cancel this order?';
                    }

                    if (confirm(confirmText)) {
                        submitAction(id, action, message);
                    }
                }

                if (e.target.classList.contains('open-modal')) {
                    closeAllDropdowns();

                    const action = e.target.dataset.action;
                    const id = e.target.dataset.id;

                    prescriptionIdInput.value = id;
                    actionTypeInput.value = action;

                    if (action === 'approve') {
                        modalTitle.textContent = 'Approve Order';
                        form.action = `/admin/orders/${id}/approve`;
                    } else if (action === 'cancel') {
                        modalTitle.textContent = 'Cancel Order';
                        form.action = `/admin/orders/${id}/cancel`;
                    }

                    dropdown.innerHTML = '<option value="">-- Select a message --</option>';
                    messages[action].forEach(msg => {
                        const opt = document.createElement('option');
                        opt.value = msg;
                        opt.textContent = msg;
                        dropdown.appendChild(opt);
                    });

                    modal.style.display = 'flex';
                }
            });

            function submitAction(id, action, message) {
                const csrfToken = getCSRFToken();
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('id', id);
                formData.append('message', message);

                fetch(`/admin/orders/${id}/${action}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Action completed successfully!');
                            window.location.reload();
                        } else {
                            alert(data.message || 'Action failed!');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing the request.');
                    });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const action = actionTypeInput.value;
                const id = prescriptionIdInput.value;
                const message = dropdown.value;
                const customMessage = document.getElementById('customMessage').value.trim();

                if (!message) {
                    alert('Please select a message.');
                    return;
                }

                const formData = new FormData();
                formData.append('_token', getCSRFToken());
                // Remove this line: formData.append('id', id);
                formData.append('message', message);
                if (customMessage) {
                    formData.append('custom_message', customMessage);
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;

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
                            modal.style.display = 'none';
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
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
            });

            document.getElementById('cancelModal').addEventListener('click', () => {
                modal.style.display = 'none';
                document.getElementById('customMessage').value = '';
                dropdown.selectedIndex = 0;
            });

            const manageModal = document.getElementById('manageOrderModal');
            let currentPrescriptionId = null;
            const selectedProductsByOrder = {};
            const availableList = document.getElementById('availableProducts');
            const selectedList = document.getElementById('selectedProducts');
            const productSearchInput = document.getElementById('productSearch');

            const qtyModal = document.getElementById('productQuantityModal');
            const qtyInput = document.getElementById('productQty');
            const productModalName = document.getElementById('productModalName');
            let selectedProductLi = null;

            productSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('#availableProducts li').forEach(li => {
                    const productName = li.dataset.name;
                    li.style.display = productName.includes(searchTerm) ? '' : 'none';
                });
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('manage-order-btn')) {
                    closeAllDropdowns();
                    currentPrescriptionId = e.target.dataset.id;
                    manageModal.style.display = 'flex';
                    loadSavedProducts(currentPrescriptionId);
                }
            });

            document.getElementById('cancelManageOrder').addEventListener('click', () => {
                manageModal.style.display = 'none';
            });

            availableList.addEventListener('click', e => {
                if (e.target.tagName === 'LI') {
                    const stockLevel = parseInt(e.target.dataset.stock);
                    if (stockLevel <= 0) {
                        alert('This product is out of stock.');
                        return;
                    }

                    selectedProductLi = e.target;
                    qtyInput.value = 1;
                    qtyInput.max = stockLevel;
                    productModalName.textContent = selectedProductLi.dataset.product;
                    qtyModal.style.display = 'flex';
                }
            });

            document.getElementById('cancelQtyModal').addEventListener('click', () => {
                qtyModal.style.display = 'none';
                selectedProductLi = null;
            });

            document.getElementById('increaseQty').addEventListener('click', () => {
                const currentQty = parseInt(qtyInput.value);
                const maxStock = parseInt(selectedProductLi.dataset.stock);
                if (currentQty < maxStock) {
                    qtyInput.value = currentQty + 1;
                } else {
                    alert(`Maximum stock available: ${maxStock}`);
                }
            });

            document.getElementById('decreaseQty').addEventListener('click', () => {
                if (qtyInput.value > 1) {
                    qtyInput.value = parseInt(qtyInput.value) - 1;
                }
            });

            qtyInput.addEventListener('input', () => {
                const currentQty = parseInt(qtyInput.value);
                const maxStock = parseInt(selectedProductLi?.dataset.stock || 999);

                if (currentQty < 1) {
                    qtyInput.value = 1;
                } else if (currentQty > maxStock) {
                    qtyInput.value = maxStock;
                    alert(`Maximum stock available: ${maxStock}`);
                }
            });

            document.getElementById('confirmQtyModal').addEventListener('click', () => {
                const quantity = parseInt(qtyInput.value);
                const id = selectedProductLi.dataset.id;
                const name = selectedProductLi.dataset.product;
                const price = selectedProductLi.dataset.price;
                const maxStock = parseInt(selectedProductLi.dataset.stock);

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

                qtyModal.style.display = 'none';
                selectedProductLi = null;
            });

            function loadSavedProducts(prescriptionId) {
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

            document.getElementById('saveSelection').addEventListener('click', function(e) {
                e.preventDefault();

                const selectedProducts = selectedProductsByOrder[currentPrescriptionId] || [];

                if (!selectedProducts.length) {
                    alert("No products selected.");
                    return;
                }

                const saveBtn = document.getElementById('saveSelection');
                const originalText = saveBtn.textContent;
                saveBtn.textContent = 'Saving...';
                saveBtn.disabled = true;

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
                                quantity: parseInt(item.quantity)
                            }))
                        })
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.log('Error response:', text);
                                throw new Error(
                                    `HTTP error! status: ${response.status}, response: ${text.substring(0, 200)}...`
                                );
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message || "Products saved successfully!");
                            loadSavedProducts(currentPrescriptionId);
                            manageModal.style.display = 'none';
                        } else {
                            alert(data.message || "Error saving products.");
                        }
                    })
                    .catch(error => {
                        console.error('Full error:', error);
                        alert(`Error: ${error.message}`);
                    })
                    .finally(() => {
                        saveBtn.textContent = originalText;
                        saveBtn.disabled = false;
                    });
            });

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

                        const summaryDiv = document.getElementById('orderSummary');

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

                            summaryDiv.innerHTML = summaryHTML;
                        } else {
                            summaryDiv.innerHTML =
                                '<p>No products selected yet. Please manage the order first.</p>';
                        }

                        const completeModal = document.getElementById('completeOrderModal');
                        completeModal.style.display = 'block';
                        completeModal.classList.add('active');

                    } catch (error) {
                        console.error('Error loading order summary:', error);
                        alert(
                            `Error loading order summary: ${error.message}. Please ensure products are saved first.`
                        );
                    }
                }
            });

            const submitOrderBtn = document.getElementById('submitOrderBtn');
            if (submitOrderBtn) {
                submitOrderBtn.addEventListener('click', async (e) => {
                    e.preventDefault();

                    if (!currentOrderPrescriptionId) {
                        alert('Error: No prescription selected. Please try again.');
                        return;
                    }

                    const submitBtn = document.getElementById('submitOrderBtn');
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Processing...';
                    submitBtn.disabled = true;

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

                            const completeModal = document.getElementById('completeOrderModal');
                            completeModal.style.display = 'none';
                            completeModal.classList.remove('active');
                            currentOrderPrescriptionId = null;

                            window.location.reload();
                        } else {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        }

                    } catch (error) {
                        console.error('Error completing order:', error);
                        alert(`Failed to complete order: ${error.message}`);
                    } finally {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }
                });
            }

            const cancelCompleteModal = document.getElementById('cancelCompleteModal');
            if (cancelCompleteModal) {
                cancelCompleteModal.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const completeModal = document.getElementById('completeOrderModal');
                    completeModal.style.display = 'none';
                    completeModal.classList.remove('active');
                    currentOrderPrescriptionId = null;
                });
            }

            const completeOrderModal = document.getElementById('completeOrderModal');
            if (completeOrderModal) {
                completeOrderModal.addEventListener('click', (e) => {
                    if (e.target === e.currentTarget) {
                        completeOrderModal.style.display = 'none';
                        completeOrderModal.classList.remove('active');
                        currentOrderPrescriptionId = null;
                    }
                });
            }

            [modal, manageModal, qtyModal].forEach(modalElement => {
                if (modalElement) {
                    modalElement.addEventListener('click', (e) => {
                        if (e.target === e.currentTarget) {
                            modalElement.style.display = 'none';
                            if (modalElement === manageModal) {
                                currentPrescriptionId = null;
                            }
                            if (modalElement === qtyModal) {
                                selectedProductLi = null;
                            }
                        }
                    });
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        if (menu !== this.nextElementSibling) menu.classList.remove('show');
                    });
                    this.nextElementSibling.classList.toggle('show');
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.action-dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape") {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });
        });
    </script>
</body>

</html>
