<head>
    <link rel="stylesheet" href="{{ asset('css/batches.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Add CSS Variables and Modal Styles -->
    <style>
        :root {
            --color-bg-primary: #ffffff;
            --color-border-light: #e5e7eb;
            --font-size-xl: 1.25rem;
            --font-weight-semibold: 600;
            --color-text-primary: #1f2937;
            --color-text-secondary: #6b7280;
            --radius-lg: 12px;
            --radius: 8px;
            --font-size-sm: 14px;
            --font-size-xs: 12px;
            --font-weight-medium: 500;
            --color-primary: #3b82f6;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Modal Styles */
        .modal-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        .modal-bg.show {
            display: flex;
        }

        .modal {
            background: #ffffff;
            padding: 0;
            border-radius: 12px;
            position: relative;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            animation: modalSlideIn 0.2s ease-out;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px 12px 0 0;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: none;
            border: none;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #1f2937;
        }

        .form-container {
            padding: 2rem;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: #ffffff;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input:focus + .form-label,
        .form-input:not(:placeholder-shown) + .form-label,
        .form-input[readonly] + .form-label {
            transform: translateY(-28px) scale(0.85);
            color: #3b82f6;
        }

        .form-label {
            position: absolute;
            left: 16px;
            top: 12px;
            background: #ffffff;
            padding: 0 4px;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            pointer-events: none;
            transition: all 0.2s ease;
            transform-origin: left top;
        }

        .help-text {
            display: block;
            margin-top: 0.5rem;
            color: #6b7280;
            font-size: 12px;
            padding-left: 4px;
        }

        .required-indicator {
            color: #ef4444;
        }

        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(420px);
            transition: transform 0.3s ease;
            z-index: 10000;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            border-left: 4px solid #10b981;
        }

        .notification-error {
            border-left: 4px solid #ef4444;
        }

        .notification-content {
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification-message {
            flex: 1;
            font-size: 14px;
            color: #374151;
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 18px;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            margin-left: 12px;
        }

        .notification-close:hover {
            color: #374151;
        }
    </style>
</head>

<div class="batches-container">
    <div class="product-header">
        <div class="product-title">
            <h2>{{ $product->product_name }}</h2>
            <span class="product-code">{{ $product->product_code }}</span>
        </div>
        <div class="product-meta">
            <div class="meta-item">
                <span class="meta-label">Brand</span>
                <span class="meta-value">{{ $product->brand_name ?? '-' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Type</span>
                <span class="meta-value">{{ $product->product_type }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Total Stock</span>
                <span class="meta-value stock-count">{{ $product->stock_quantity }}</span>
            </div>
        </div>
    </div>

    @if ($product->batches->count() > 0)
        <div class="batches-table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th class="col-batch">Batch Number</th>
                        <th class="col-supplier">Supplier</th>
                        <th class="col-numeric">Received</th>
                        <th class="col-numeric">Remaining</th>
                        <th class="col-numeric">Unit Cost</th>
                        <th class="col-numeric">Sale Price</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($product->batches as $batch)
                        @php
                            $isExpired = \Carbon\Carbon::parse($batch->expiration_date)->isPast();
                            $isExpiringSoon = \Carbon\Carbon::parse($batch->expiration_date)->diffInDays(now()) <= 30;
                            $isOutOfStock = $batch->quantity_remaining <= 0;
                            $rowClass = $isExpired ? 'row-expired' : ($isExpiringSoon ? 'row-expiring' : '');
                        @endphp
                        <tr class="batch-row {{ $rowClass }}">
                            <td class="batch-number">
                                <div class="batch-info">
                                    <div class="batch-id">{{ $batch->batch_number }}</div>
                                    <div class="batch-expiry">
                                        <span class="expiry-label">Expires:</span>
                                        <span
                                            class="expiry-date {{ $isExpired ? 'expired' : ($isExpiringSoon ? 'warning' : 'safe') }}">
                                            {{ \Carbon\Carbon::parse($batch->expiration_date)->format('M d, Y') }}
                                        </span>
                                        @if ($isExpired)
                                            <span class="expiry-status expired">Expired</span>
                                        @elseif($isExpiringSoon)
                                            <span class="expiry-status warning">
                                                {{ intval(\Carbon\Carbon::now()->diffInMonths(\Carbon\Carbon::parse($batch->expiration_date), false)) }}
                                                Months left
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="supplier-name">
                                {{ $batch->supplier->name ?? ($product->supplier->name ?? '-') }}
                            </td>
                            <td class="quantity received">
                                {{ number_format($batch->quantity_received) }}
                            </td>
                            <td class="quantity remaining">
                                <span class="qty-value {{ $isOutOfStock ? 'out-of-stock' : 'in-stock' }}">
                                    {{ number_format($batch->quantity_remaining) }}
                                </span>
                            </td>
                            <td class="price cost">
                                <span class="currency">₱</span>{{ number_format($batch->unit_cost, 2) }}
                            </td>
                            <td class="price sale">
                                <div class="price-container">
                                    <span class="price-main">
                                        <span class="currency">₱</span>{{ number_format($batch->sale_price, 2) }}
                                    </span>
                                    @if ($batch->unit_cost > 0)
                                        @php
                                            $margin =
                                                (($batch->sale_price - $batch->unit_cost) / $batch->unit_cost) * 100;
                                        @endphp
                                        <span class="margin {{ $margin > 20 ? 'high-margin' : 'low-margin' }}">
                                            {{ number_format($margin, 1) }}%
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="batch-status">
                                @if ($isExpired)
                                    <span class="status-badge expired">Expired</span>
                                @elseif($isOutOfStock)
                                    <span class="status-badge out-of-stock">Out of Stock</span>
                                @elseif($isExpiringSoon)
                                    <span class="status-badge expiring">Expiring Soon</span>
                                @else
                                    <span class="status-badge active">Active</span>
                                @endif
                            </td>
                            <td class="batch-actions">
                                <div class="action-buttons">
                                    @if (!$isExpired)
                                        <button class="btn-action add-stock"
                                            onclick="openAddStockToBatchModal({{ $batch->id }}, '{{ $batch->batch_number }}', '{{ $batch->expiration_date }}', {{ $batch->unit_cost }})"
                                            title="Add Stock to This Batch">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M12 5v14M5 12h14" />
                                            </svg>
                                            Add Stock
                                        </button>
                                    @endif

                                    <button class="btn-action update-price"
                                        onclick="openPricingModal({{ $batch->id }}, '{{ $batch->batch_number }}', {{ $batch->unit_cost }}, {{ $batch->sale_price }})"
                                        title="Update Pricing">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                                        </svg>
                                        Edit Price
                                    </button>
                                    @if (!$isExpired && $batch->quantity_remaining > 0)
                                        <button class="btn-action stock-out"
                                            onclick="inventoryOpenStockOutModal({{ $batch->id }}, '{{ $batch->batch_number }}', {{ $batch->quantity_remaining }}, '{{ addslashes($product->product_name) }}')"
                                            title="Remove Stock">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18l-2 13H5L3 6z" />
                                                <path d="M16 10v4M8 10v4" />
                                            </svg>
                                            Stock Out
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-value">{{ $product->batches->count() }}</div>
                <div class="card-label">Total Batches</div>
            </div>
            <div class="summary-card">
                <div class="card-value active">{{ $product->batches->where('quantity_remaining', '>', 0)->count() }}
                </div>
                <div class="card-label">Active Batches</div>
            </div>
            <div class="summary-card">
                <div class="card-value">{{ number_format($product->batches->sum('quantity_remaining')) }}</div>
                <div class="card-label">Total Stock</div>
            </div>
            <div class="summary-card">
                <div class="card-value expired">
                    {{ $product->batches->filter(function ($batch) {return \Carbon\Carbon::parse($batch->expiration_date)->isPast();})->count() }}
                </div>
                <div class="card-label">Expired</div>
            </div>
            <div class="summary-card">
                <div class="card-value warning">
                    {{ $product->batches->filter(function ($batch) {return \Carbon\Carbon::parse($batch->expiration_date)->diffInDays(now()) <= 30 && !\Carbon\Carbon::parse($batch->expiration_date)->isPast();})->count() }}
                </div>
                <div class="card-label">Expiring Soon</div>
            </div>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                    <circle cx="9" cy="9" r="2" />
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                </svg>
            </div>
            <h3>No Batches Found</h3>
            <p>This product doesn't have any batches yet. Batches are created when inventory is received.</p>
        </div>
    @endif
</div>

<!-- Add Stock to Batch Modal -->
<div class="modal-bg" id="addStockToBatchModal">
    <div class="modal fade-in">
        <div class="modal-close" onclick="closeAddStockToBatchModal()">&times;</div>
        <div class="modal-header">Add Stock to Batch</div>
        <form method="POST" id="addStockToBatchForm" class="form-container">
            @csrf
            <input type="hidden" name="batch_id" id="batch_id">

            <div class="form-group">
                <input type="text" class="form-input" id="batch_info" readonly placeholder=" ">
                <label class="form-label">Batch Information</label>
            </div>

            <div class="form-group">
                <input type="number" class="form-input" name="additional_quantity" placeholder=" " required min="1">
                <label class="form-label">Additional Quantity <span class="required-indicator">*</span></label>
                <div class="help-text">Quantity to add to this batch</div>
            </div>

            <div class="form-group">
                <input type="number" class="form-input" name="unit_cost" id="modal_unit_cost" placeholder=" " required step="0.01" min="0">
                <label class="form-label">Unit Cost (₱) <span class="required-indicator">*</span></label>
                <div class="help-text">Must match existing batch unit cost</div>
            </div>

            <div class="form-group">
                <input type="date" class="form-input" name="received_date" placeholder=" " required>
                <label class="form-label">Received Date <span class="required-indicator">*</span></label>
            </div>

            <div class="form-group">
                <textarea class="form-input" name="notes" rows="3" placeholder=" "></textarea>
                <label class="form-label">Notes</label>
                <div class="help-text">Optional notes for this stock addition</div>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="closeAddStockToBatchModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Price Modal -->
<div class="modal-bg" id="editPriceModal">
    <div class="modal fade-in">
        <div class="modal-close" onclick="closePricingModal()">&times;</div>
        <div class="modal-header">Update Batch Pricing</div>
        <form method="POST" id="editPriceForm" class="form-container">
            @csrf
            @method('PUT')
            <input type="hidden" name="batch_id" id="price_batch_id">

            <div class="form-group">
                <input type="text" class="form-input" id="price_batch_info" readonly placeholder=" ">
                <label class="form-label">Batch Information</label>
            </div>

            <div class="form-group">
                <input type="number" class="form-input" name="unit_cost" id="price_unit_cost" placeholder=" " required step="0.01" min="0">
                <label class="form-label">Unit Cost (₱) <span class="required-indicator">*</span></label>
            </div>

            <div class="form-group">
                <input type="number" class="form-input" name="sale_price" id="price_sale_price" placeholder=" " required step="0.01" min="0">
                <label class="form-label">Sale Price (₱) <span class="required-indicator">*</span></label>
            </div>

            <div class="form-group">
                <textarea class="form-input" name="notes" rows="2" placeholder=" "></textarea>
                <label class="form-label">Notes</label>
                <div class="help-text">Optional notes for this price update</div>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="closePricingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Price</button>
            </div>
        </form>
    </div>
</div>

<script>
// Define modal functions globally - These work with AJAX loaded content
window.openAddStockToBatchModal = function(batchId, batchNumber, expirationDate, unitCost) {
    console.log('Opening add stock modal:', { batchId, batchNumber, expirationDate, unitCost });

    const modal = document.getElementById('addStockToBatchModal');
    if (!modal) {
        console.error('Add stock modal not found');
        showNotification('Modal not found. Please refresh the page.', 'error');
        return;
    }

    // Show modal with animation
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);

    // Set form data
    const batchIdInput = document.getElementById('batch_id');
    const batchInfoInput = document.getElementById('batch_info');
    const unitCostInput = document.getElementById('modal_unit_cost');
    const receivedDateInput = document.querySelector('[name="received_date"]');

    if (batchIdInput) batchIdInput.value = batchId;
    if (batchInfoInput) {
        const formattedDate = new Date(expirationDate).toLocaleDateString();
        batchInfoInput.value = `Batch #${batchNumber} - Expires: ${formattedDate}`;
    }
    if (unitCostInput) unitCostInput.value = parseFloat(unitCost).toFixed(2);
    if (receivedDateInput) {
        receivedDateInput.value = new Date().toISOString().split('T')[0];
    }

    // Set form action URL
    const form = document.getElementById('addStockToBatchForm');
    if (form) {
        form.action = `/inventory/batches/${batchId}/add-stock`;
    }

    // Focus on first input
    setTimeout(() => {
        const firstInput = document.querySelector('[name="additional_quantity"]');
        if (firstInput) firstInput.focus();
    }, 100);

    console.log('Add stock modal opened successfully');
};

window.closeAddStockToBatchModal = function() {
    const modal = document.getElementById('addStockToBatchModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);

        const form = document.getElementById('addStockToBatchForm');
        if (form) {
            form.reset();
            const batchIdInput = document.getElementById('batch_id');
            const batchInfoInput = document.getElementById('batch_info');
            if (batchIdInput) batchIdInput.value = '';
            if (batchInfoInput) batchInfoInput.value = '';
        }
    }
};

window.openPricingModal = function(batchId, batchNumber, unitCost, salePrice) {
    console.log('Opening pricing modal:', { batchId, batchNumber, unitCost, salePrice });

    const modal = document.getElementById('editPriceModal');
    if (!modal) {
        console.error('Edit price modal not found');
        showNotification('Modal not found. Please refresh the page.', 'error');
        return;
    }

    // Show modal with animation
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);

    // Set form data
    const batchIdInput = document.getElementById('price_batch_id');
    const batchInfoInput = document.getElementById('price_batch_info');
    const unitCostInput = document.getElementById('price_unit_cost');
    const salePriceInput = document.getElementById('price_sale_price');

    if (batchIdInput) batchIdInput.value = batchId;
    if (batchInfoInput) batchInfoInput.value = `Batch #${batchNumber}`;
    if (unitCostInput) unitCostInput.value = parseFloat(unitCost).toFixed(2);
    if (salePriceInput) salePriceInput.value = parseFloat(salePrice).toFixed(2);

    // Set form action URL
    const form = document.getElementById('editPriceForm');
    if (form) {
        form.action = `/inventory/batches/${batchId}/update-price`;
    }

    // Focus on first input
    setTimeout(() => {
        if (unitCostInput) unitCostInput.focus();
    }, 100);

    console.log('Pricing modal opened successfully');
};

window.closePricingModal = function() {
    const modal = document.getElementById('editPriceModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);

        const form = document.getElementById('editPriceForm');
        if (form) {
            form.reset();
            const batchIdInput = document.getElementById('price_batch_id');
            const batchInfoInput = document.getElementById('price_batch_info');
            if (batchIdInput) batchIdInput.value = '';
            if (batchInfoInput) batchInfoInput.value = '';
        }
    }
};

// Form submission handlers
function handleFormSubmission(form, buttonText, successMessage) {
    const submitButton = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showNotification('Security token not found. Please refresh the page.', 'error');
        resetSubmitButton(submitButton, buttonText);
        return;
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(successMessage, 'success');
            if (form.id === 'addStockToBatchForm') {
                closeAddStockToBatchModal();
            } else if (form.id === 'editPriceForm') {
                closePricingModal();
            }
            if (data.reload) {
                setTimeout(() => window.location.reload(), 1000);
            }
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showNotification('Server error. Please try again.', 'error');
    })
    .finally(() => {
        resetSubmitButton(submitButton, buttonText);
    });
}

function resetSubmitButton(button, text) {
    if (button) {
        button.disabled = false;
        button.textContent = text;
    }
}

function validateAddStockForm(formData) {
    if (!formData.get('additional_quantity') || formData.get('additional_quantity') <= 0) {
        showNotification('Please enter a valid quantity', 'error');
        return false;
    }
    if (!formData.get('unit_cost') || formData.get('unit_cost') <= 0) {
        showNotification('Please enter a valid unit cost', 'error');
        return false;
    }
    if (!formData.get('received_date')) {
        showNotification('Please select a received date', 'error');
        return false;
    }
    return true;
}

function validatePriceForm(formData) {
    if (!formData.get('unit_cost') || formData.get('unit_cost') <= 0) {
        showNotification('Please enter a valid unit cost', 'error');
        return false;
    }
    if (!formData.get('sale_price') || formData.get('sale_price') <= 0) {
        showNotification('Please enter a valid sale price', 'error');
        return false;
    }
    return true;
}

// Notification system
window.showNotification = function(message, type = 'info') {
    try {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 100);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);

        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            });
        }
    } catch (error) {
        console.error('Error showing notification:', error);
        alert(message);
    }
};

// Initialize event handlers when DOM is ready
function initializeModalHandlers() {
    console.log('Initializing modal event handlers...');

    // Add Stock Form Handler
    const addStockForm = document.getElementById('addStockToBatchForm');
    if (addStockForm && !addStockForm.hasAttribute('data-handler-attached')) {
        addStockForm.setAttribute('data-handler-attached', 'true');
        addStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            if (validateAddStockForm(formData)) {
                handleFormSubmission(this, 'Add Stock', 'Stock added successfully!');
            }
        });
        console.log('Add stock form handler attached');
    }

    // Edit Price Form Handler
    const editPriceForm = document.getElementById('editPriceForm');
    if (editPriceForm && !editPriceForm.hasAttribute('data-handler-attached')) {
        editPriceForm.setAttribute('data-handler-attached', 'true');
        editPriceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            if (validatePriceForm(formData)) {
                handleFormSubmission(this, 'Update Price', 'Price updated successfully!');
            }
        });
        console.log('Edit price form handler attached');
    }

    // Modal click outside handlers
    const addStockModal = document.getElementById('addStockToBatchModal');
    const editPriceModal = document.getElementById('editPriceModal');

    if (addStockModal && !addStockModal.hasAttribute('data-click-handler')) {
        addStockModal.setAttribute('data-click-handler', 'true');
        addStockModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddStockToBatchModal();
            }
        });
    }

    if (editPriceModal && !editPriceModal.hasAttribute('data-click-handler')) {
        editPriceModal.setAttribute('data-click-handler', 'true');
        editPriceModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePricingModal();
            }
        });
    }

    console.log('Modal handlers initialized successfully');
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - Setting up modal functionality');
    initializeModalHandlers();

    // Global escape key handler
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const addStockModal = document.getElementById('addStockToBatchModal');
            const editPriceModal = document.getElementById('editPriceModal');

            if (addStockModal && addStockModal.style.display === 'flex') {
                closeAddStockToBatchModal();
            }
            if (editPriceModal && editPriceModal.style.display === 'flex') {
                closePricingModal();
            }
        }
    });

    console.log('Modal functions defined globally and ready for AJAX content');
});

// Reinitialize function for AJAX loaded content
window.reinitializeModals = function() {
    console.log('Reinitializing modals after AJAX content load');
    setTimeout(() => {
        initializeModalHandlers();
    }, 100);
};

console.log('All modal functions loaded and ready');
</script>
