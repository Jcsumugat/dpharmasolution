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
                        <th>Available Stock</th>
                        <th>Batches</th>
                        <th>Earliest Expiry</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            // Calculate available stock (non-expired batches only)
                            $availableStock = $product->batches
                                ->where('quantity_remaining', '>', 0)
                                ->where('expiration_date', '>', now())
                                ->sum('quantity_remaining');

                            // Calculate expired stock
                            $expiredStock = $product->batches
                                ->where('quantity_remaining', '>', 0)
                                ->where('expiration_date', '<=', now())
                                ->sum('quantity_remaining');

                            // Count available batches (non-expired)
                            $availableBatches = $product->batches
                                ->where('quantity_remaining', '>', 0)
                                ->where('expiration_date', '>', now())
                                ->count();

                            // Check if low stock based on available stock
                            $isLowStock = $product->reorder_level && $availableStock <= $product->reorder_level;

                            // Get earliest expiring NON-EXPIRED batch
                            $earliestAvailableBatch = $product->batches
                                ->where('quantity_remaining', '>', 0)
                                ->where('expiration_date', '>', now())
                                ->sortBy('expiration_date')
                                ->first();
                        @endphp
                        <tr class="{{ $isLowStock ? 'low-stock' : '' }}">
                            <td><strong>{{ $product->product_code }}</strong></td>
                            <td>{{ $product->product_name }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ $product->product_type }}</span>
                            </td>
                            <td>
                                <div>
                                    <span
                                        class="{{ $isLowStock ? 'text-danger' : ($availableStock <= 0 ? 'text-danger' : '') }}">
                                        <strong>{{ number_format($availableStock) }}</strong>
                                    </span>
                                    <small class="text-muted">{{ $product->getUnitDisplay() }}</small>

                                    @if ($expiredStock > 0)
                                        <br><small class="text-danger">{{ number_format($expiredStock) }}
                                            expired</small>
                                    @endif
                                    @if ($product->reorder_level)
                                        <br><small class="text-muted">Min: {{ $product->reorder_level }}
                                            {{ $product->getUnitDisplay() }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <button class="btn-link" onclick="inventoryViewBatches({{ $product->id }})"
                                    style="color: rgb(26, 25, 25);">
                                    {{ $availableBatches }}
                                    {{ $availableBatches === 1 ? 'available' : 'available' }}
                                    @if ($product->batches->count() > $availableBatches)
                                        <br><small
                                            class="text-muted">{{ $product->batches->count() - $availableBatches }}
                                            expired</small>
                                    @endif
                                </button>
                            </td>
                            <td>
                                @if ($earliestAvailableBatch)
                                    @php
                                        $daysUntilExpiry = intval(
                                            now()->diffInDays($earliestAvailableBatch->expiration_date, false),
                                        );
                                    @endphp
                                    <span
                                        class="{{ $daysUntilExpiry <= 30 ? 'text-warning' : ($daysUntilExpiry <= 7 ? 'text-danger' : '') }}">
                                        {{ \Carbon\Carbon::parse($earliestAvailableBatch->expiration_date)->format('M d, Y') }}
                                        @if ($daysUntilExpiry <= 30)
                                            <br><small>({{ $daysUntilExpiry }} days left)</small>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-danger">No available stock</span>
                                @endif
                            </td>
                            <td>
                                @if ($availableStock <= 0 && $expiredStock > 0)
                                    <span class="badge badge-danger">Expired Stock Only</span>
                                @elseif($isLowStock)
                                    <span class="badge badge-warning">Low Stock</span>
                                @elseif($availableStock <= 0)
                                    <span class="badge badge-danger">Out of Stock</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm"
                                        onclick="inventoryOpenBatchModal({{ $product->id }}, '{{ addslashes($product->product_name) }}')">
                                        Add Batch
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding: 3rem;">
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

    <!-- Enhanced Add Batch Modal with Unit Override for Inventory Page -->
    <div class="modal-bg" id="inventoryBatchModal" style="display: none;">
        <div class="modal" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <div class="modal-title">
                    Add New Batch - <span id="inventoryModalProductName"></span>
                </div>
                <div class="modal-close" onclick="inventoryCloseBatchModal()">&times;</div>
            </div>
            <div class="modal-body">
                <form method="POST" id="inventoryBatchForm" action="">
                    @csrf
                    <input type="hidden" id="inventoryCurrentProductId" name="product_id" value="">
                    <input type="hidden" id="inventoryProductDefaultUnit" value="">
                    <input type="hidden" id="inventoryProductDefaultUnitQuantity" value="">

                    <div class="form-grid">
                        <!-- First row: Quantity and Expiration Date -->
                        <div class="form-group">
                            <input type="number" name="package_quantity" id="inventory_package_quantity"
                                placeholder=" " required min="0.01" step="0.01">
                            <label for="inventory_package_quantity" id="inventory_package_label">How many
                                units?</label>
                            <input type="hidden" name="quantity_received" id="inventory_quantity_received">
                            <div class="calculation-preview" id="inventory_calculation_preview"
                                style="margin-top: 8px; padding: 10px; background: #f0fdf4; border: 2px solid #86efac; border-radius: 6px; display: none;">
                                <div style="font-size: 0.875rem; color: #166534; font-weight: 600;">
                                    <span id="inventory_preview_text"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <input type="date" name="expiration_date" id="inventory_expiration_date"
                                placeholder=" " required>
                            <label for="inventory_expiration_date">Expiration Date</label>
                        </div>

                        <!-- Second row: Unit Cost and Sale Price -->
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

                        <!-- Third row: Received Date and Supplier -->
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

                        <!-- NEW: Packaging Override Section -->
                        <div class="form-group full-width"
                            style="background: #f0f4ff; padding: 16px; border-radius: 8px; margin: 16px 0; border: 2px solid #d0e0ff;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <input type="checkbox" id="inventory_override_unit"
                                    onchange="inventoryToggleUnitOverride(this.checked)"
                                    style="width: 20px; height: 20px; cursor: pointer; margin: 0;">
                                <label for="inventory_override_unit"
                                    style="margin: 0; font-weight: 600; color: #1e40af; cursor: pointer; font-size: 0.95rem;">
                                    📦 This batch has different packaging than usual
                                </label>
                            </div>
                            <div id="inventory_default_unit_info"
                                style="margin-top: 8px; padding: 8px 12px; background: white; border-radius: 6px; border-left: 3px solid #3b82f6;">
                                <small style="color: #64748b; display: block; margin-bottom: 4px;">Default
                                    packaging:</small>
                                <strong id="inventory_default_unit_display"
                                    style="color: #1e293b; font-size: 0.95rem;">Loading...</strong>
                            </div>
                            <small class="help-text" style="margin-top: 8px; display: block; color: #64748b;">
                                💡 Check the box above if this shipment came in different packaging (e.g., 100mL bottles
                                instead of the usual 60mL)
                            </small>
                        </div>

                        <!-- Unit Override Fields (hidden by default) -->
                        <div id="inventory_unit_override_section" style="display: none; grid-column: 1 / -1;">
                            <div
                                style="background: #fff7ed; border: 2px solid #fed7aa; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                                <h4 style="margin: 0 0 12px 0; color: #c2410c; font-size: 0.95rem; font-weight: 600;">
                                    ⚠️ Custom Packaging for This Batch
                                </h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <select name="unit" id="inventory_unit">
                                            <option value="">Use Product Default</option>

                                            <optgroup label="Bottled/Container Products">
                                                <option value="bottle">Bottle (syrup, suspension, liquid)</option>
                                                <option value="dropper_bottle">Dropper Bottle (eye/ear drops)</option>
                                                <option value="topical_bottle">Bottle (lotion, solution)</option>
                                                <option value="jar">Jar (ointment, cream)</option>
                                                <option value="tube">Tube (cream, ointment, gel)</option>
                                            </optgroup>

                                            <optgroup label="Injectable Products">
                                                <option value="vial">Vial</option>
                                                <option value="ampoule">Ampoule</option>
                                                <option value="syringe">Pre-filled Syringe</option>
                                            </optgroup>

                                            <optgroup label="Solid Dose Packaging">
                                                <option value="blister_pack">Blister Pack</option>
                                                <option value="strip">Strip</option>
                                                <option value="box">Box</option>
                                                <option value="sachet">Sachet</option>
                                            </optgroup>

                                            <optgroup label="Respiratory">
                                                <option value="nebule">Nebule</option>
                                                <option value="inhaler">Inhaler</option>
                                            </optgroup>

                                            <optgroup label="Other">
                                                <option value="patch">Patch</option>
                                                <option value="suppository">Suppository</option>
                                                <option value="piece">Piece (individual items)</option>
                                                <option value="pack">Pack (multi-item)</option>
                                            </optgroup>
                                        </select>
                                        <label for="inventory_unit">Unit Type</label>
                                    </div>

                                    <div class="form-group">
                                        <input type="number" name="unit_quantity" id="inventory_unit_quantity"
                                            placeholder=" " step="0.01" min="0.01">
                                        <label for="inventory_unit_quantity">Quantity per Unit</label>
                                        <div class="help-text" id="inventory_unit_quantity_help">
                                            For tablets: 1. For 60mL bottle: 60. For 10-tablet blister: 10
                                        </div>
                                    </div>

                                    <div class="form-group full-width">
                                        <div
                                            style="background: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 8px; padding: 12px 16px;">
                                            <label
                                                style="font-size: 0.875rem; font-weight: 500; color: #065f46; margin-bottom: 4px; display: block;">
                                                ✓ This Batch Will Display As:
                                            </label>
                                            <div
                                                style="font-size: 1.1rem; font-weight: 600; color: #047857; min-height: 28px;">
                                                <span id="inventory_unit_preview_text"
                                                    style="color: #6b7280; font-style: italic; font-weight: 400;">
                                                    Select unit type above
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full width row: Notes -->
                        <div class="form-group full-width">
                            <textarea name="notes" id="inventory_notes" placeholder=" " rows="3" maxlength="1000"></textarea>
                            <label for="inventory_notes">Notes (optional)</label>
                            <small class="help-text">Maximum 1000 characters</small>
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

    <!-- View Batches Modal -->
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

    <!-- Stock Out Modal -->
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
                            Information:</label>
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
        const inventoryUnitDisplayMap = {
            'bottle': 'Bottle',
            'vial': 'Vial',
            'ampoule': 'Ampoule',
            'dropper_bottle': 'Dropper Bottle',
            'nebule': 'Nebule',
            'blister_pack': 'Blister Pack',
            'box': 'Box',
            'strip': 'Strip',
            'sachet': 'Sachet',
            'syringe': 'Pre-filled Syringe',
            'tube': 'Tube',
            'jar': 'Jar',
            'topical_bottle': 'Bottle',
            'inhaler': 'Inhaler',
            'patch': 'Patch',
            'suppository': 'Suppository',
            'piece': 'Piece',
            'pack': 'Pack'
        };

        let inventoryCurrentBatchId = null;
        let inventoryMaxQuantity = 0;
        let inventoryCurrentProductId = null;
        let inventoryCurrentProductName = null;

        document.addEventListener('DOMContentLoaded', function() {
            initializeFormValidation();
            initializeUnitPreviewListeners();
            initializePackageCalculator();
        });

        // SINGLE CONSOLIDATED openBatchModal function
        function inventoryOpenBatchModal(productId, productName = '') {
            inventoryCurrentProductId = productId;
            inventoryCurrentProductName = productName;

            const modal = document.getElementById('inventoryBatchModal');
            const form = document.getElementById('inventoryBatchForm');

            if (!modal || !form) {
                alert('Error: Modal elements not found. Please refresh the page.');
                return;
            }

            // Reset form
            form.reset();
            form.action = `/dashboard/inventory/${productId}/batches`;

            // Set product info
            document.getElementById('inventoryCurrentProductId').value = productId;
            document.getElementById('inventoryModalProductName').textContent = productName;
            document.getElementById('inventory_default_unit_display').textContent = 'Loading...';

            // Reset override section
            document.getElementById('inventory_override_unit').checked = false;
            inventoryToggleUnitOverride(false);

            // Set dates
            const today = new Date().toISOString().split('T')[0];

            // Reset package calculator
            document.getElementById('inventory_package_quantity').value = '';
            updatePackageCalculation();

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);


            document.getElementById('inventory_received_date').value = today;
            document.getElementById('inventory_expiration_date').min = tomorrow.toISOString().split('T')[0];

            // Fetch product details including default units
            fetch(`/api/products/${productId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch product details');
                    return response.json();
                })
                .then(product => {
                    // Store default unit info
                    document.getElementById('inventoryProductDefaultUnit').value = product.unit || 'piece';
                    document.getElementById('inventoryProductDefaultUnitQuantity').value = product.unit_quantity || 1;

                    // Display default unit
                    const unitDisplay = inventoryFormatUnitDisplay(product.unit || 'piece', product.unit_quantity || 1);
                    document.getElementById('inventory_default_unit_display').textContent = unitDisplay;

                    // Pre-fill supplier and price if available
                    if (product.supplier_id) {
                        document.getElementById('inventory_supplier_id').value = product.supplier_id;
                    }
                    if (product.price) {
                        document.getElementById('inventory_sale_price').value = product.price;
                    }
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    document.getElementById('inventory_default_unit_display').textContent =
                        'Error loading default unit';
                });

            // Show modal and focus first input
            modal.style.display = 'flex';
            setTimeout(() => {
                const firstInput = form.querySelector('input[name="package_quantity"]');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function inventoryCloseBatchModal() {
            const modal = document.getElementById('inventoryBatchModal');
            if (modal) {
                modal.style.display = 'none';
            }
            inventoryCurrentProductId = null;
            inventoryCurrentProductName = null;
        }

        function inventoryToggleUnitOverride(show) {
            const section = document.getElementById('inventory_unit_override_section');
            section.style.display = show ? 'block' : 'none';

            if (!show) {
                // Reset override fields
                document.getElementById('inventory_unit').value = '';
                document.getElementById('inventory_unit_quantity').value = '';
                document.getElementById('inventory_unit_preview_text').textContent = 'Select unit type above';
                document.getElementById('inventory_unit_preview_text').style.fontStyle = 'italic';
                document.getElementById('inventory_unit_preview_text').style.fontWeight = '400';
                document.getElementById('inventory_unit_preview_text').style.color = '#6b7280';
            }

            // Update package calculator
            updatePackageCalculation();
        }

        // Format unit display
        function inventoryFormatUnitDisplay(unit, quantity) {
            if (!unit) return 'No unit specified';

            const unitLabel = inventoryUnitDisplayMap[unit] || unit;
            const qty = parseFloat(quantity) || 1;

            if (qty === 1) {
                return `1 ${unitLabel}`;
            } else {
                return `${qty} ${unitLabel}${qty > 1 && !['ml', 'L'].includes(unit) ? 's' : ''}`;
            }
        }

        const inventoryContainerUnits = ['bottle', 'vial', 'ampoule', 'dropper_bottle', 'nebule',
            'tube', 'jar', 'topical_bottle', 'syringe'
        ];


        const inventoryMultiItemUnits = ['blister_pack', 'strip', 'box', 'pack', 'sachet'];

        function initializeUnitPreviewListeners() {
            const unitSelect = document.getElementById('inventory_unit');
            const quantityInput = document.getElementById('inventory_unit_quantity');
            const previewText = document.getElementById('inventory_unit_preview_text');

            function updatePreview() {
                const unit = unitSelect.value;
                const quantity = quantityInput.value || 1;

                if (unit) {
                    previewText.textContent = inventoryFormatUnitDisplay(unit, quantity);
                    previewText.style.fontStyle = 'normal';
                    previewText.style.fontWeight = '600';
                    previewText.style.color = '#047857';

                    // Update package calculator when override changes
                    updatePackageCalculation();
                } else {
                    previewText.textContent = 'Select unit type above';
                    previewText.style.fontStyle = 'italic';
                    previewText.style.fontWeight = '400';
                    previewText.style.color = '#6b7280';
                }
            }

            if (unitSelect && quantityInput && previewText) {
                unitSelect.addEventListener('change', updatePreview);
                quantityInput.addEventListener('input', updatePreview);
            }
        }
        // Package to pieces calculator
        function initializePackageCalculator() {
            const packageInput = document.getElementById('inventory_package_quantity');
            const hiddenQuantityInput = document.getElementById('inventory_quantity_received');
            const previewDiv = document.getElementById('inventory_calculation_preview');
            const previewText = document.getElementById('inventory_preview_text');
            const packageLabel = document.getElementById('inventory_package_label');

            if (packageInput) {
                packageInput.addEventListener('input', updatePackageCalculation);
            }
        }

        function updatePackageCalculation() {
            const packageInput = document.getElementById('inventory_package_quantity');
            const hiddenQuantityInput = document.getElementById('inventory_quantity_received');
            const previewDiv = document.getElementById('inventory_calculation_preview');
            const previewText = document.getElementById('inventory_preview_text');
            const packageLabel = document.getElementById('inventory_package_label');
            const overrideChecked = document.getElementById('inventory_override_unit').checked;

            if (!packageInput || !hiddenQuantityInput || !previewDiv || !previewText) return;

            const packageQty = parseFloat(packageInput.value) || 0;

            // Determine which unit to use
            let unit, unitQuantity;

            if (overrideChecked) {
                // Use override values
                unit = document.getElementById('inventory_unit').value;
                unitQuantity = parseFloat(document.getElementById('inventory_unit_quantity').value) || 1;

                // If override is checked but unit not selected yet, don't show preview
                if (!unit) {
                    previewDiv.style.display = 'none';
                    packageLabel.textContent = 'How many units received?';
                    hiddenQuantityInput.value = '';
                    return;
                }
            } else {
                // Use product defaults
                unit = document.getElementById('inventoryProductDefaultUnit').value || 'piece';
                unitQuantity = parseFloat(document.getElementById('inventoryProductDefaultUnitQuantity').value) || 1;
            }

            // Calculate total pieces
            const totalPieces = Math.round(packageQty * unitQuantity);

            // Update hidden field with total pieces
            hiddenQuantityInput.value = totalPieces;

            // Update label
            const unitLabel = inventoryUnitDisplayMap[unit] || unit;
            packageLabel.textContent = `How many ${unitLabel}${packageQty !== 1 ? 's' : ''} received?`;

            // Show/hide preview based on calculation
            if (packageQty > 0 && unitQuantity > 1) {
                // Show calculation preview
                previewDiv.style.display = 'block';

                // Format the preview text
                const formattedPackageQty = packageQty % 1 === 0 ? packageQty : packageQty.toFixed(2);
                const unitText = packageQty === 1 ? unitLabel : `${unitLabel}s`;

                previewText.innerHTML = `
            <span style="font-size: 0.95rem;">${formattedPackageQty} ${unitText}</span>
            <span style="margin: 0 8px; color: #16a34a;">×</span>
            <span style="font-size: 0.95rem;">${unitQuantity} pieces</span>
            <span style="margin: 0 8px; color: #16a34a;">=</span>
            <span style="font-size: 1.1rem; color: #15803d; font-weight: 700;">${totalPieces.toLocaleString()} pieces</span>
            <span style="margin-left: 6px; color: #16a34a;">✓</span>
        `;
            } else if (packageQty > 0 && unitQuantity === 1) {
                // Simple 1:1 conversion, minimal preview
                previewDiv.style.display = 'block';
                previewText.innerHTML = `
            <span style="font-size: 1.1rem; color: #15803d; font-weight: 700;">${totalPieces} piece${totalPieces !== 1 ? 's' : ''}</span>
            <span style="margin-left: 6px; color: #16a34a;">✓</span>
        `;
            } else {
                previewDiv.style.display = 'none';
            }
        }

        function inventoryViewBatches(productId, productName = '') {
            console.log('Opening batches for product:', productId, productName);

            inventoryCurrentProductId = productId;
            inventoryCurrentProductName = productName;

            const modal = document.getElementById('inventoryBatchesModal');
            const content = document.getElementById('inventoryBatchesContent');

            if (!modal || !content) {
                console.error('Modal or content element not found');
                alert('Error: Modal elements not found. Please refresh the page.');
                return;
            }

            const modalTitle = document.getElementById('inventoryBatchesModalTitle');
            if (modalTitle && productName) {
                modalTitle.textContent = `Batches for ${productName}`;
            }

            modal.style.display = 'flex';
            content.innerHTML =
                '<div class="loading-spinner" style="text-align: center; padding: 40px;">Loading batches...</div>';

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            };

            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
            }

            fetch(`/dashboard/inventory/${productId}/batches`, {
                    method: 'GET',
                    headers: headers,
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 404) {
                            throw new Error('Product not found. Please refresh the page and try again.');
                        } else if (response.status === 403) {
                            throw new Error('Access denied. Please check your permissions.');
                        } else if (response.status === 500) {
                            throw new Error('Server error. Please try again later.');
                        } else {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                    }
                    return response.text();
                })
                .then(html => {
                    if (html.trim() === '') {
                        throw new Error('Empty response received from server.');
                    }

                    content.innerHTML = html;
                    initializeBatchActions();
                    initializeBatchModalFunctions();
                })
                .catch(error => {
                    console.error('Error loading batches:', error);
                    let errorMessage = 'Error loading batches: ' + error.message;

                    if (error.message.includes('Failed to fetch')) {
                        errorMessage += '<br><small>Check your internet connection or try refreshing the page.</small>';
                    }

                    content.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc2626;">
                    <div>${errorMessage}</div>
                    <button onclick="inventoryViewBatches(${productId}, '${productName.replace(/'/g, "\\'")}')"
                            class="btn btn-sm btn-primary" style="margin-top: 15px;">
                        Try Again
                    </button>
                </div>
            `;
                });
        }

        function inventoryCloseBatchesModal() {
            const modal = document.getElementById('inventoryBatchesModal');
            if (modal) {
                modal.style.display = 'none';
            }
            inventoryCurrentProductId = null;
            inventoryCurrentProductName = null;
        }

        function inventoryOpenStockOutModal(batchId, batchNumber, remainingQuantity, productName) {
            inventoryCurrentBatchId = batchId;
            inventoryMaxQuantity = remainingQuantity;

            const elements = {
                'inventory_stock_out_batch_id': batchId,
                'inventory_stock_out_product_id': inventoryCurrentProductId || '',
                'inventory_product_display_name': productName,
                'inventory_batch_display_number': batchNumber,
                'inventory_batch_display_stock': remainingQuantity.toLocaleString(),
                'inventory_max_quantity': remainingQuantity.toLocaleString()
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
                batchDisplayRow.style.display = 'table-row';
            }

            const quantityInput = document.getElementById('inventory_stock_out_quantity');
            if (quantityInput) {
                quantityInput.max = remainingQuantity;
            }

            const form = document.getElementById('inventoryStockOutForm');
            if (form) {
                form.reset();
                document.getElementById('inventory_stock_out_batch_id').value = batchId;
                document.getElementById('inventory_stock_out_product_id').value = inventoryCurrentProductId || '';
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
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        inventoryCloseStockOutModal();
                        showNotification(data.message, 'success');

                        if (inventoryCurrentProductId && document.getElementById('inventoryBatchesModal').style
                            .display === 'flex') {
                            setTimeout(() => {
                                inventoryViewBatches(inventoryCurrentProductId, inventoryCurrentProductName);
                            }, 1000);
                        }

                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert(data.message || 'Error removing stock');
                    }
                })
                .catch(error => {
                    alert('Error removing stock: ' + error.message);
                });
        }

        // Batch modal functions
        function openAddStockToBatchModal(batchId, batchNumber, expirationDate, unitCost) {
            const modal = document.getElementById('addStockToBatchModal');
            if (!modal) {
                showNotification('Modal not found. Please refresh the page.', 'error');
                return;
            }

            modal.classList.add('show');

            const batchIdInput = document.getElementById('batch_id');
            const batchInfoInput = document.getElementById('batch_info');
            const unitCostInput = document.getElementById('modal_unit_cost');
            const receivedDateInput = document.querySelector('#addStockToBatchForm [name="received_date"]');

            if (batchIdInput) batchIdInput.value = batchId;
            if (batchInfoInput) {
                const formattedDate = new Date(expirationDate).toLocaleDateString();
                batchInfoInput.value = `Batch #${batchNumber} - Expires: ${formattedDate}`;
            }
            if (unitCostInput) {
                unitCostInput.value = parseFloat(unitCost).toFixed(2);
                unitCostInput.readOnly = true;
            }
            if (receivedDateInput) {
                receivedDateInput.value = new Date().toISOString().split('T')[0];
            }

            const form = document.getElementById('addStockToBatchForm');
            if (form) {
                form.action = `/inventory/batch/${batchId}/add-stock`;
            }

            setTimeout(() => {
                const firstInput = document.querySelector('#addStockToBatchForm [name="additional_quantity"]');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeAddStockToBatchModal() {
            const modal = document.getElementById('addStockToBatchModal');
            if (modal) {
                modal.classList.remove('show');
                const form = document.getElementById('addStockToBatchForm');
                if (form) {
                    setTimeout(() => form.reset(), 200);
                }
            }
        }

        function openPricingModal(batchId, batchNumber, unitCost, salePrice) {
            const modal = document.getElementById('editPriceModal');
            if (!modal) {
                showNotification('Modal not found. Please refresh the page.', 'error');
                return;
            }

            modal.classList.add('show');

            const batchIdInput = document.getElementById('price_batch_id');
            const batchInfoInput = document.getElementById('price_batch_info');
            const unitCostInput = document.getElementById('price_unit_cost');
            const salePriceInput = document.getElementById('price_sale_price');

            if (batchIdInput) batchIdInput.value = batchId;
            if (batchInfoInput) batchInfoInput.value = `Batch #${batchNumber}`;
            if (unitCostInput) {
                unitCostInput.value = parseFloat(unitCost).toFixed(2);
                unitCostInput.readOnly = true;
            }
            if (salePriceInput) salePriceInput.value = parseFloat(salePrice).toFixed(2);

            const form = document.getElementById('editPriceForm');
            if (form) {
                form.action = `/dashboard/inventory/batches/${batchId}/update-price`;
            }

            setTimeout(() => {
                if (salePriceInput) salePriceInput.focus();
            }, 100);
        }

        function closePricingModal() {
            const modal = document.getElementById('editPriceModal');
            if (modal) {
                modal.classList.remove('show');
                const form = document.getElementById('editPriceForm');
                if (form) {
                    setTimeout(() => form.reset(), 200);
                }
            }
        }

        function initializeBatchModalFunctions() {
            const addStockForm = document.getElementById('addStockToBatchForm');
            if (addStockForm && !addStockForm.hasAttribute('data-handler-attached')) {
                addStockForm.setAttribute('data-handler-attached', 'true');
                addStockForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmission(this, 'Add Stock', 'Stock added successfully!');
                });
            }

            const editPriceForm = document.getElementById('editPriceForm');
            if (editPriceForm && !editPriceForm.hasAttribute('data-handler-attached')) {
                editPriceForm.setAttribute('data-handler-attached', 'true');
                editPriceForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmission(this, 'Update Price', 'Price updated successfully!');
                });
            }
        }

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
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = buttonText;
                }
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
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
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

                        if (inventoryCurrentProductId && document.getElementById('inventoryBatchesModal').style
                            .display === 'flex') {
                            setTimeout(() => {
                                inventoryViewBatches(inventoryCurrentProductId, inventoryCurrentProductName);
                            }, 1000);
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
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = buttonText;
                    }
                });
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6'
            };

            const notification = document.createElement('div');
            notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function initializeBatchActions() {
            // Placeholder for batch-specific action initialization
            console.log('Batch actions initialized');
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

                    // If override checkbox is NOT checked, remove unit fields from formData
                    if (!document.getElementById('inventory_override_unit').checked) {
                        formData.delete('unit');
                        formData.delete('unit_quantity');
                    }

                    fetch(this.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                inventoryCloseBatchModal();
                                showNotification(data.message || 'Batch added successfully!', 'success');
                                setTimeout(() => location.reload(), 1500);
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
        }

        // Handle modal clicks and escape key
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-bg')) {
                if (e.target.id === 'inventoryBatchModal') inventoryCloseBatchModal();
                else if (e.target.id === 'inventoryBatchesModal') inventoryCloseBatchesModal();
                else if (e.target.id === 'inventoryStockOutModal') inventoryCloseStockOutModal();
                else if (e.target.id === 'addStockToBatchModal') closeAddStockToBatchModal();
                else if (e.target.id === 'editPriceModal') closePricingModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                inventoryCloseBatchModal();
                inventoryCloseBatchesModal();
                inventoryCloseStockOutModal();
                closeAddStockToBatchModal();
                closePricingModal();
            }
        });

        // Make functions globally available
        window.inventoryOpenBatchModal = inventoryOpenBatchModal;
        window.inventoryCloseBatchModal = inventoryCloseBatchModal;
        window.inventoryViewBatches = inventoryViewBatches;
        window.inventoryOpenStockOutModal = inventoryOpenStockOutModal;
        window.openAddStockToBatchModal = openAddStockToBatchModal;
        window.openPricingModal = openPricingModal;
    </script>
</body>
@include('admin.admin-footer')

</html>
