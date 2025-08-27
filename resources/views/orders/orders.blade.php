<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Orders</title>
    <link rel="stylesheet" href="{{ asset('css/orders.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    @include('admin.admin-header')

    <div class="content">
        <h2 class="page-title">Prescription Orders</h2>

        @foreach (['success', 'info', 'error'] as $msg)
        @if(session($msg))
        <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }}">{{ session($msg) }}</div>
        @endif
        @endforeach

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer ID</th>
                    <th>Mobile</th>
                    <th>Note</th>
                    <th>Order Status</th>
                    <th>Prescription File</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prescriptions as $prescription)
                <tr>
                    <td>{{ $prescription->order->order_id ?? 'N/A' }}</td>
                    <td>
                        @if($prescription->customer)
                        <small>ID: {{ $prescription->customer->customer_id }}</small><br>
                        {{ $prescription->customer->email_address ?? 'N/A' }}
                        @else
                        <em>Guest Order</em>
                        @endif
                    </td>
                    <td>{{ $prescription->mobile_number }}</td>
                    <td>{{ $prescription->notes ?? 'None' }}</td>
                    <td>
                        <span class="status-badge {{ strtolower($prescription->order->status ?? 'pending') }}">
                            {{ ucfirst($prescription->order->status ?? 'Pending') }}
                        </span>
                    </td>
                    <td>
                        @if($prescription->is_encrypted && $prescription->file_path)
                        <div class="prescription-file-info encrypted-file">
                            <strong>{{ $prescription->original_filename ?? 'Encrypted File' }}</strong>
                            <span class="security-badge">🔒 ENCRYPTED</span>
                            @if($prescription->file_size)
                            <div class="file-size">Size: {{ number_format($prescription->file_size / 1024, 1) }} KB</div>
                            @endif
                            <div class="file-actions">
                                @if(str_starts_with($prescription->file_mime_type ?? '', 'image/'))
                                <a href="{{ route('prescription.file.view', $prescription->id) }}"
                                    target="_blank"
                                    class="btn-file btn-view">
                                    👁️ View Image
                                </a>
                                @endif
                                <a href="{{ route('prescription.file.download', $prescription->id) }}"
                                    class="btn-file btn-download">
                                    📥 Download
                                </a>
                            </div>
                            <div style="font-size: 0.75em; color: #6c757d; margin-top: 2px;">
                                Type: {{ $prescription->file_mime_type ?? 'Unknown' }}
                            </div>
                        </div>
                        @elseif($prescription->file_path)
                        <div class="prescription-file-info legacy-file">
                            <strong>Legacy File</strong>
                            <span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold;">
                                ⚠️ UNENCRYPTED
                            </span>
                            <div class="file-actions">
                                <a href="{{ asset('storage/' . $prescription->file_path) }}"
                                    target="_blank"
                                    class="btn-file btn-view">
                                    👁️ View File
                                </a>
                            </div>
                            <div style="font-size: 0.75em; color: #856404; margin-top: 2px;">
                                This file was uploaded before encryption was implemented
                            </div>
                        </div>
                        @else
                        <span class="no-file">No prescription file</span>
                        @endif
                    </td>
                    <td>{{ $prescription->created_at->format('Y-m-d H:i') }}</td>
                    <td class="action-cell">
                        <div class="action-dropdown">
                            <button class="dropdown-trigger" data-id="{{ $prescription->id }}">&#8230;</button>
                            <div class="dropdown-menu">
                                <button class="dropdown-item manage manage-order-btn" data-id="{{ $prescription->id }}">
                                    📝 Manage Order
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item approve open-modal" data-action="approve" data-id="{{ $prescription->id }}">
                                    ✅ Approve Order
                                </button>
                                <button class="dropdown-item partial open-modal" data-action="partialApprove" data-id="{{ $prescription->id }}">
                                    ⚠️ Partial Approval
                                </button>
                                <button class="dropdown-item cancel open-modal" data-action="cancel" data-id="{{ $prescription->id }}">
                                    ❌ Cancel Order
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item complete complete-order-btn" data-id="{{ $prescription->id }}">
                                    🎯 Complete Order
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="dropdown-overlay"></div>

        <form id="prescriptionForm" method="POST" style="display: none;">
            @csrf
            <div id="productInputsContainer"></div>
        </form>

        <div id="messageModal" class="modal">
            <div class="modal-content">
                <h3>Send a Message</h3>
                <form id="actionForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="prescriptionId" />
                    <label for="messageSelect">Choose a message:</label>
                    <select id="messageSelect" name="message" class="dropdown" required>
                        <option value="">-- Select a message --</option>
                    </select>
                    <div class="modal-actions">
                        <button type="button" id="cancelModal" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="manageOrderModal" class="modal">
            <div class="modal-content">
                <h3>Manage Order</h3>
                <input type="text" id="productSearch" placeholder="🔍 Search products..." />
                <div style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <h4>Available Products</h4>
                        <ul id="availableProducts">
                            @foreach($products as $product)
                            @php
                            $totalStock = $product->batches ? $product->batches->sum('quantity_remaining') : 0;
                            @endphp
                            <li data-name="{{ strtolower($product->product_name) }}"
                                data-id="{{ $product->id }}"
                                data-product="{{ $product->product_name }}"
                                data-price="{{ $product->current_sale_price ?? $product->sale_price ?? 0 }}"
                                data-stock="{{ $totalStock }}">
                                {{ $product->product_name }} ({{ $product->brand_name ?? 'No Brand' }}) - ₱{{ number_format($product->current_sale_price ?? $product->sale_price ?? 0, 2) }} | Stock: {{ $totalStock }}
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
                <h3>📝 Confirm Order Completion</h3>
                <div id="orderSummary">
                    <p>Selected products will be displayed here...</p>
                </div>
                <div class="modal-buttons">
                    <button class="btn btn-primary" id="submitOrderBtn">Submit</button>
                    <button class="btn btn-secondary" id="cancelCompleteModal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            let currentOrderPrescriptionId = null;
            const messages = {
                approve: [
                    'Your order has been approved. We are preparing your order.',
                    'Your order is ready to pick up.'
                ],
                partialApprove: [
                    'Your order is partially approved due to stock shortages of some products.',
                    'Your order is partially approved due to products unavailability.',
                    'Some items are not in stock. We have prepared the rest of your order for pickup.'
                ],
                cancel: [
                    'Your order has been cancelled because no products are available from your prescription.',
                    'Unfortunately, none of the requested products are currently in stock. Sorry for the inconvenience.'
                ]
            };

            const dropdownOverlay = document.querySelector('.dropdown-overlay');

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

            function getCSRFToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    document.querySelector('input[name="_token"]')?.value;
            }

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('open-modal')) {
                    closeAllDropdowns();

                    const action = e.target.dataset.action;
                    const id = e.target.dataset.id;
                    prescriptionIdInput.value = id;
                    form.action = `/orders/${id}/${action.replace(/([A-Z])/g, '-$1').toLowerCase()}`;
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

            document.getElementById('cancelModal').addEventListener('click', () => modal.style.display = 'none');

            const manageModal = document.getElementById('manageOrderModal');
            let currentPrescriptionId = null;
            const selectedProductsByOrder = {};
            const availableList = document.getElementById('availableProducts');
            const selectedList = document.getElementById('selectedProducts');
            const searchInput = document.getElementById('productSearch');

            const qtyModal = document.getElementById('productQuantityModal');
            const qtyInput = document.getElementById('productQty');
            const productModalName = document.getElementById('productModalName');
            let selectedProductLi = null;

            searchInput.addEventListener('input', function() {
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
                    const availableLi = document.querySelector(`#availableProducts li[data-id="${product.id}"]`);
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
                        selectedProductsByOrder[currentPrescriptionId] = selectedProductsByOrder[currentPrescriptionId].filter(p => p.id !== productId);
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

                const form = document.getElementById('prescriptionForm');
                form.action = `/prescriptions/${currentPrescriptionId}/items`;

                const container = document.getElementById('productInputsContainer');
                container.innerHTML = '';

                const csrfToken = getCSRFToken();

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            items: selectedProducts.map(item => ({
                                id: item.id,
                                quantity: item.quantity
                            }))
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
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
                        console.error('Error:', error);
                        if (error.message.includes('404')) {
                            alert("Route not found. Please check if the backend route exists.");
                        } else if (error.message.includes('500')) {
                            alert("Server error. Please check the backend implementation.");
                        } else {
                            alert(`Error: ${error.message}`);
                        }
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
                            let summaryHTML = '<h4>📋 Order Summary:</h4>';
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
                            summaryDiv.innerHTML = '<p>❌ No products selected yet. Please manage the order first.</p>';
                        }

                        const completeModal = document.getElementById('completeOrderModal');
                        completeModal.style.display = 'block';
                        completeModal.classList.add('active');

                    } catch (error) {
                        console.error('Error loading order summary:', error);
                        alert(`Error loading order summary: ${error.message}. Please ensure products are saved first.`);
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
                            throw new Error('CSRF token not found. Please ensure the CSRF meta tag is in your HTML head.');
                        }

                        const paymentMethodElement = document.getElementById('paymentMethod');
                        const orderNotesElement = document.getElementById('orderNotes');

                        const paymentMethod = paymentMethodElement ? paymentMethodElement.value : 'cash';
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
                            throw new Error(`Server returned invalid JSON. Status: ${response.status}, Response: ${responseText.substring(0, 200)}...`);
                        }

                        if (response.ok && data.success) {
                            alert(`Order completed successfully!\n\nSale ID: ${data.sale_id}\nTotal Amount: ₱${parseFloat(data.total_amount).toFixed(2)}\nTotal Items: ${data.total_items}\nPayment Method: ${data.payment_method}\n\nStock has been updated automatically.`);

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
            // Dropdown logic
            document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        if (menu !== this.nextElementSibling) menu.classList.remove('show');
                    });
                    // Toggle this dropdown
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