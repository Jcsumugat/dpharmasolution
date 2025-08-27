<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/inventory.css') }}" />
</head>

<body>
    @include('admin.admin-header')

    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('stock_action'))
        <div class="flash-message flash-success" id="flashMessage">
            {{ session('stock_action') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flash-message flash-error" id="flashMessage">
            {{ session('error') }}
        </div>
    @endif



    <div class="container fade-in" id="mainContent">
        <div class="header-bar">
            <h2 class="page-title">Inventory Management</h2>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" id="inventorySortForm" class="search-sort-bar">
            <div class="search-section">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by name or product code..." class="search-input">
                <input type="hidden" name="sort" id="inventorySortInput" value="{{ request('sort') }}">
                <input type="hidden" name="direction" id="inventoryDirectionInput"
                    value="{{ request('direction', 'asc') }}">
            </div>
        </form>

        <div class="table-wrapper" style="overflow-x: auto; max-height: 80vh;">
            <table class="inventory-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th>Sale Price</th>
                        <th>Stock Level</th>
                        <th>Batches</th>
                        <th>Earliest Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $totalStock = $product->batches->sum('quantity_remaining');
                            $isLowStock = $product->reorder_level && $totalStock <= $product->reorder_level;
                        @endphp
                        <tr class="{{ $isLowStock ? 'low-stock' : '' }}">
                            <td><strong>{{ $product->product_code }}</strong></td>
                            <td>{{ $product->product_name }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ $product->product_type }}</span>
                            </td>
                            <td><strong>₱{{ number_format($product->current_sale_price ?? ($product->sale_price ?? 0), 2) }}</strong>
                            </td>
                            <td>
                                <span class="{{ $isLowStock ? 'text-danger' : '' }}">
                                    <strong>{{ number_format($totalStock) }}</strong>
                                </span>
                                @if ($product->reorder_level)
                                    <br><small class="text-muted">Min: {{ $product->reorder_level }}</small>
                                @endif
                            </td>
                            <td>
                                <button class="btn-link" onclick="inventoryViewBatches({{ $product->id }})"
                                    style="color: rgb(26, 25, 25);">
                                    {{ $product->batches->count() }}
                                    {{ $product->batches->count() === 1 ? 'batch' : 'batches' }}
                                </button>
                            </td>
                            <td>
                                @php
                                    $earliestBatch = $product->batches
                                        ->where('quantity_remaining', '>', 0)
                                        ->sortBy('expiration_date')
                                        ->first();
                                @endphp
                                @if ($earliestBatch)
                                    @php
                                        $daysUntilExpiry = intval(
                                            now()->diffInDays($earliestBatch->expiration_date, false),
                                        );
                                    @endphp
                                    <span
                                        class="{{ $daysUntilExpiry <= 30 ? 'text-warning' : ($daysUntilExpiry <= 7 ? 'text-danger' : '') }}">
                                        {{ \Carbon\Carbon::parse($earliestBatch->expiration_date)->format('M d, Y') }}
                                        @if ($daysUntilExpiry <= 30)
                                            <br><small>({{ $daysUntilExpiry > 0 ? "{$daysUntilExpiry} days to Expire" : 'Expired' }})</small>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">No stock</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm"
                                        onclick="inventoryOpenBatchModal({{ $product->id }}, '{{ addslashes($product->product_name) }}')">
                                        Add Batch
                                    </button>
                                    @if ($totalStock > 0)
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="inventoryOpenProductStockOut({{ $product->id }}, '{{ addslashes($product->product_name) }}', {{ $totalStock }})">
                                            Stock Out
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 3rem;">
                                <div class="text-muted">
                                    <svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16"
                                        style="margin-bottom: 1rem;">
                                        <path
                                            d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.371 2.371 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976l2.61-3.045zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045A.5.5 0 0 0 1 5.37v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0zM1.5 8.5A.5.5 0 0 1 2 9v6h1v-5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5h6V9a.5.5 0 0 1 1 0v6h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1V9a.5.5 0 0 1 .5-.5zM4 15h3v-5H4v5zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3zm3 0h-2v3h2v-3z" />
                                    </svg>
                                    <p><strong>No products found</strong></p>
                                    <p>Try adjusting your search criteria or add new products to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-bg" id="inventoryBatchModal" style="display: none;">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                Add New Batch - <span id="inventoryModalProductName"></span>
                <div class="modal-close" onclick="inventoryCloseBatchModal()">&times;</div>
            </div>
            <div class="modal-body">
                <form method="POST" id="inventoryBatchForm" action="">
                    @csrf
                    <input type="hidden" id="inventoryCurrentProductId" name="product_id" value="">

                    <div class="form-grid">
                        <div class="form-group">
                            <input type="date" name="expiration_date" id="inventory_expiration_date"
                                placeholder=" " required>
                            <label for="inventory_expiration_date">Expiration Date</label>
                        </div>

                        <div class="form-group">
                            <input type="number" step="0.01" name="unit_cost" id="inventory_unit_cost"
                                placeholder=" " required min="0">
                            <label for="inventory_unit_cost">Unit Cost (₱)</label>
                        </div>

                        <div class="form-group">
                            <input type="number" step="0.01" name="sale_price" id="inventory_sale_price"
                                placeholder=" " min="0">
                            <label for="inventory_sale_price">Sale Price (₱)</label>
                        </div>

                        <div class="form-group">
                            <input type="date" name="received_date" id="inventory_received_date" placeholder=" "
                                required>
                            <label for="inventory_received_date">Received Date</label>
                        </div>

                        <div class="form-group">
                            <select name="supplier_id" id="inventory_supplier_id">
                                <option value="">Use Product Default</option>
                                @foreach ($suppliers ?? [] as $supplier)
                                    @if (isset($supplier) && is_object($supplier))
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <label for="inventory_supplier_id">Supplier</label>
                        </div>

                        <div class="form-group full-width">
                            <textarea name="notes" id="inventory_notes" placeholder=" " rows="3"></textarea>
                            <label for="inventory_notes">Notes (optional)</label>
                        </div>
                    </div>

                    <div class="modal-buttons">
                        <button type="button" class="btn btn-cancel"
                            onclick="inventoryCloseBatchModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="inventoryBatchSubmitBtn">Add
                            Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-bg" id="inventoryBatchesModal" style="display:none;">
        <div class="modal" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" id="inventoryBatchesModalTitle">
                Product Batches
                <div class="modal-close" onclick="inventoryCloseBatchesModal()">&times;</div>
            </div>
            <div class="modal-body">
                <div id="inventoryBatchesContent">
                    <div class="loading-spinner">Loading batches...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-bg" id="inventoryStockOutModal" style="display:none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                Remove Stock
                <div class="modal-close" onclick="inventoryCloseStockOutModal()">&times;</div>
            </div>
            <div class="modal-body">
                <form id="inventoryStockOutForm" onsubmit="inventoryHandleStockOut(event)">
                    @csrf
                    <input type="hidden" id="inventory_stock_out_batch_id" name="batch_id">
                    <input type="hidden" id="inventory_stock_out_product_id" name="product_id">

                    <div class="form-group">
                        <label
                            style="position: static; background: none; padding: 0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Item
                            Information</label>
                        <div class="batch-info-display">
                            <p><strong>Product:</strong> <span id="inventory_product_display_name"></span></p>
                            <p id="inventory_batch_display_row" style="display: none;"><strong>Batch:</strong> <span
                                    id="inventory_batch_display_number"></span></p>
                            <p><strong>Available Stock:</strong> <span id="inventory_batch_display_stock"></span> units
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="number" name="stock_out" id="inventory_stock_out_quantity" placeholder=" "
                            required min="1">
                        <label for="inventory_stock_out_quantity">Quantity to Remove</label>
                        <small class="help-text">Maximum: <span id="inventory_max_quantity"></span> units</small>
                    </div>

                    <div class="form-group">
                        <select name="reason" id="inventory_stock_out_reason" required>
                            <option value="">Select Reason</option>
                            <option value="sale">Sale/Customer Purchase</option>
                            <option value="damage">Damaged/Defective</option>
                            <option value="expired">Expired Product</option>
                            <option value="theft">Theft/Loss</option>
                            <option value="adjustment">Inventory Adjustment</option>
                            <option value="return">Return to Supplier</option>
                            <option value="transfer">Transfer to Another Location</option>
                            <option value="sample">Sample/Testing</option>
                            <option value="other">Other Reason</option>
                        </select>
                        <label for="inventory_stock_out_reason">Reason for Stock Out</label>
                    </div>

                    <div class="form-group">
                        <textarea name="notes" id="inventory_stock_out_notes" placeholder=" " rows="3" maxlength="500"></textarea>
                        <label for="inventory_stock_out_notes">Additional Notes (Optional)</label>
                        <small class="help-text">Max 500 characters</small>
                    </div>

                    <div class="modal-buttons">
                        <button type="button" class="btn btn-cancel"
                            onclick="inventoryCloseStockOutModal()">Cancel</button>
                        <button type="submit" class="btn btn-danger">Remove Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @stack('scripts')
    <script>
        let inventoryCurrentBatchId = null;
        let inventoryMaxQuantity = 0;
        let inventoryCurrentProductId = null;
        let inventoryCurrentProductName = null;

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const receivedDateInput = document.getElementById('inventory_received_date');
            if (receivedDateInput) {
                receivedDateInput.value = today;
            }

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const expirationDateInput = document.getElementById('inventory_expiration_date');
            if (expirationDateInput) {
                expirationDateInput.min = tomorrow.toISOString().split('T')[0];
            }

            initializeFormValidation();
        });

        function inventoryToggleDropdown() {
            const dropdown = document.getElementById('inventoryDropdown');
            if (dropdown) {
                dropdown.classList.toggle('open');
            }
        }

        function inventoryApplySort(column) {
            const sortInput = document.getElementById('inventorySortInput');
            const directionInput = document.getElementById('inventoryDirectionInput');
            const sortForm = document.getElementById('inventorySortForm');

            if (sortInput && directionInput && sortForm) {
                if (sortInput.value === column) {
                    directionInput.value = directionInput.value === 'asc' ? 'desc' : 'asc';
                } else {
                    sortInput.value = column;
                    directionInput.value = 'asc';
                }
                sortForm.submit();
            }
        }

        function inventoryOpenBatchModal(productId, productName = '') {
            inventoryCurrentProductId = productId;
            inventoryCurrentProductName = productName;

            const modal = document.getElementById('inventoryBatchModal');
            const form = document.getElementById('inventoryBatchForm');
            const productIdInput = document.getElementById('inventoryCurrentProductId');
            const modalProductName = document.getElementById('inventoryModalProductName');

            if (!modal || !form) {
                alert('Error: Modal elements not found. Please refresh the page.');
                return;
            }

            form.action = `/dashboard/inventory/${productId}/batches`;

            if (productIdInput) {
                productIdInput.value = productId;
            }

            if (modalProductName) {
                modalProductName.textContent = productName;
            }

            form.reset();

            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);

            const receivedDateInput = document.getElementById('inventory_received_date');
            const expirationDateInput = document.getElementById('inventory_expiration_date');

            if (receivedDateInput) {
                receivedDateInput.value = today;
            }

            if (expirationDateInput) {
                expirationDateInput.min = tomorrow.toISOString().split('T')[0];
            }

            if (productIdInput) {
                productIdInput.value = productId;
            }

            modal.style.display = 'flex';

            const firstInput = form.querySelector('input[name="quantity_received"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        function inventoryCloseBatchModal() {
            const modal = document.getElementById('inventoryBatchModal');
            if (modal) {
                modal.style.display = 'none';
            }
            inventoryCurrentProductId = null;
            inventoryCurrentProductName = null;
        }

        function inventoryViewBatches(productId) {
            inventoryCurrentProductId = productId;
            const modal = document.getElementById('inventoryBatchesModal');
            const content = document.getElementById('inventoryBatchesContent');

            if (modal && content) {
                modal.style.display = 'flex';
                content.innerHTML = '<div class="loading-spinner">Loading batches...</div>';

                fetch(`/dashboard/inventory/${productId}/batches`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {
                        content.innerHTML = html;
                    })
                    .catch(error => {
                        content.innerHTML =
                            '<div style="text-align: center; padding: 40px; color: #dc2626;">Error loading batches: ' +
                            error.message + '</div>';
                    });
            }
        }

        function inventoryCloseBatchesModal() {
            const modal = document.getElementById('inventoryBatchesModal');
            if (modal) {
                modal.style.display = 'none';
            }
            inventoryCurrentProductId = null;
        }

        function inventoryOpenStockOutModal(batchId, batchNumber, availableStock, productName) {
            inventoryCurrentBatchId = batchId;
            inventoryMaxQuantity = availableStock;

            const elements = {
                'inventory_stock_out_batch_id': batchId,
                'inventory_stock_out_product_id': inventoryCurrentProductId,
                'inventory_product_display_name': productName,
                'inventory_batch_display_number': batchNumber,
                'inventory_batch_display_stock': availableStock.toLocaleString(),
                'inventory_max_quantity': availableStock.toLocaleString()
            };

            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    if (element.tagName === 'INPUT') {
                        element.value = value;
                    } else {
                        element.textContent = value;
                    }
                }
            });

            const batchDisplayRow = document.getElementById('inventory_batch_display_row');
            if (batchDisplayRow) {
                batchDisplayRow.style.display = 'block';
            }

            const quantityInput = document.getElementById('inventory_stock_out_quantity');
            if (quantityInput) {
                quantityInput.max = availableStock;
            }

            const form = document.getElementById('inventoryStockOutForm');
            if (form) {
                form.reset();
                document.getElementById('inventory_stock_out_batch_id').value = batchId;
                document.getElementById('inventory_stock_out_product_id').value = inventoryCurrentProductId;
            }

            const modal = document.getElementById('inventoryStockOutModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function inventoryOpenProductStockOut(productId, productName, availableStock) {
            inventoryCurrentProductId = productId;
            inventoryMaxQuantity = availableStock;

            const elements = {
                'inventory_stock_out_batch_id': '',
                'inventory_stock_out_product_id': productId,
                'inventory_product_display_name': productName,
                'inventory_batch_display_stock': availableStock.toLocaleString(),
                'inventory_max_quantity': availableStock.toLocaleString()
            };

            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    if (element.tagName === 'INPUT') {
                        element.value = value;
                    } else {
                        element.textContent = value;
                    }
                }
            });

            const batchDisplayRow = document.getElementById('inventory_batch_display_row');
            if (batchDisplayRow) {
                batchDisplayRow.style.display = 'none';
            }

            const quantityInput = document.getElementById('inventory_stock_out_quantity');
            if (quantityInput) {
                quantityInput.max = availableStock;
            }

            const form = document.getElementById('inventoryStockOutForm');
            if (form) {
                form.reset();
                document.getElementById('inventory_stock_out_batch_id').value = '';
                document.getElementById('inventory_stock_out_product_id').value = productId;
            }

            const modal = document.getElementById('inventoryStockOutModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function inventoryCloseStockOutModal() {
            const modal = document.getElementById('inventoryStockOutModal');
            if (modal) {
                modal.style.display = 'none';
            }
            inventoryCurrentBatchId = null;
            inventoryMaxQuantity = 0;
        }

        function inventoryHandleStockOut(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const quantity = parseInt(formData.get('stock_out'));

            if (quantity > inventoryMaxQuantity) {
                alert(`Cannot remove ${quantity} units. Maximum available: ${inventoryMaxQuantity}`);
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                alert('CSRF token not found. Please refresh the page.');
                return;
            }

            fetch('/dashboard/inventory/stock-out', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        inventoryCloseStockOutModal();
                        inventoryShowFlashMessage(data.message, 'success');

                        if (inventoryCurrentProductId && document.getElementById('inventoryBatchesModal').style
                            .display === 'flex') {
                            inventoryViewBatches(inventoryCurrentProductId);
                        }

                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        alert(data.message || 'Error removing stock');
                    }
                })
                .catch(error => {
                    alert('Error removing stock: ' + error.message);
                });
        }

        function inventoryShowFlashMessage(message, type) {
            const flashDiv = document.createElement('div');
            flashDiv.className = `flash-message flash-${type}`;
            flashDiv.textContent = message;

            const contentHeader = document.querySelector('.content-header');
            if (contentHeader) {
                contentHeader.insertAdjacentElement('afterbegin', flashDiv);

                setTimeout(() => {
                    if (flashDiv && flashDiv.parentNode) {
                        flashDiv.parentNode.removeChild(flashDiv);
                    }
                }, 5000);
            }
        }

        function initializeFormValidation() {
            const batchForm = document.getElementById('inventoryBatchForm');
            if (batchForm) {
                batchForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (!this.action || this.action === '') {
                        alert('Error: Form action not set. Please try again.');
                        return;
                    }

                    const formData = new FormData(this);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');

                    if (!csrfToken) {
                        alert('CSRF token not found. Please refresh the page.');
                        return;
                    }

                    const submitBtn = document.getElementById('inventoryBatchSubmitBtn');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Adding...';

                    fetch(this.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                inventoryCloseBatchModal();
                                inventoryShowFlashMessage(data.message || 'Batch added successfully!',
                                    'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                alert(data.message || 'Error adding batch');
                            }
                        })
                        .catch(error => {
                            alert('Error adding batch: ' + error.message);
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        });
                });
            }

            const quantityInput = document.getElementById('inventory_stock_out_quantity');
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    const value = parseInt(this.value);
                    if (value > inventoryMaxQuantity && inventoryMaxQuantity > 0) {
                        this.value = inventoryMaxQuantity;
                    }
                });
            }

            window.addEventListener('click', function(event) {
                const dropdown = document.getElementById('inventoryDropdown');
                if (dropdown && !dropdown.contains(event.target)) {
                    dropdown.classList.remove('open');
                }

                const batchModal = document.getElementById('inventoryBatchModal');
                if (batchModal && event.target === batchModal) {
                    inventoryCloseBatchModal();
                }

                const batchesModal = document.getElementById('inventoryBatchesModal');
                if (batchesModal && event.target === batchesModal) {
                    inventoryCloseBatchesModal();
                }

                const stockOutModal = document.getElementById('inventoryStockOutModal');
                if (stockOutModal && event.target === stockOutModal) {
                    inventoryCloseStockOutModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const visibleModal = document.querySelector('.modal-bg[style*="flex"]');
                    if (visibleModal) {
                        const modalId = visibleModal.id;
                        switch (modalId) {
                            case 'inventoryBatchModal':
                                inventoryCloseBatchModal();
                                break;
                            case 'inventoryBatchesModal':
                                inventoryCloseBatchesModal();
                                break;
                            case 'inventoryStockOutModal':
                                inventoryCloseStockOutModal();
                                break;
                        }
                    }
                }
            });
        }

        window.inventoryOpenStockOutModal = inventoryOpenStockOutModal;
        window.inventoryViewBatches = inventoryViewBatches;
        window.inventoryShowFlashMessage = inventoryShowFlashMessage;
        window.inventoryOpenBatchModal = inventoryOpenBatchModal;
    </script>
</body>

</html>
