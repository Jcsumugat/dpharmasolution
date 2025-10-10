<head>
    <link rel="stylesheet" href="{{ asset('css/batches.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                <span class="meta-value stock-count">
                    {{ number_format($product->stock_quantity) }} {{ $product->getUnitDisplay() }}
                </span>
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
                                <small class="text-muted">pieces</small>

                            </td>
                            <td class="quantity remaining">
                                <span class="qty-value {{ $isOutOfStock ? 'out-of-stock' : 'in-stock' }}">
                                    {{ number_format($batch->quantity_remaining) }}
                                </span>
                                <small class="text-muted">pieces</small>
                            </td>
                            <td class="price cost">
                                <div class="price-info">
                                    <span class="price-main">
                                        <span class="currency">₱</span>{{ number_format($batch->unit_cost, 2) }}
                                    </span>
                                </div>
                            </td>
                            <td class="price sale">
                                <div class="price-container">
                                    <div class="price-info">
                                        <span class="price-main">
                                            <span class="currency">₱</span>{{ number_format($batch->sale_price, 2) }}
                                        </span>
                                    </div>
                                    @if ($batch->unit_cost > 0)
                                        @php
                                            $margin =
                                                (($batch->sale_price - $batch->unit_cost) / $batch->unit_cost) * 100;
                                        @endphp
                                        <div class="margin-wrapper">
                                            <span class="margin {{ $margin > 20 ? 'high-margin' : 'low-margin' }}">
                                                {{ number_format($margin, 1) }}%
                                            </span>
                                            <span class="margin-tooltip-trigger">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                                    <line x1="12" y1="17" x2="12.01" y2="17" />
                                                </svg>
                                                <span class="margin-tooltip">Profit Margin (%)</span>
                                            </span>
                                        </div>
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
                <div class="card-value">
                    {{ number_format($product->batches->sum('quantity_remaining')) }}
                </div>
                <div class="card-label">Total Stock ({{ $product->getUnitDisplay() }})</div>
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
                <input type="number" class="form-input" name="additional_quantity" placeholder=" " required
                    min="1">
                <label class="form-label">Additional Quantity <span class="required-indicator">*</span></label>
                <div class="help-text">Quantity to add to this batch</div>
            </div>

            <div class="form-group">
                <input type="number" class="form-input" name="unit_cost" id="modal_unit_cost" placeholder=" "
                    required step="0.01" min="0">
                <label class="form-label">Unit Cost (₱) <span class="required-indicator">*</span></label>
                <div class="help-text">Unit cost for this existing batch (read-only)</div>
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
                <button type="button" class="btn btn-secondary"
                    onclick="closeAddStockToBatchModal()">Cancel</button>
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
                <input type="number" class="form-input" name="unit_cost" id="price_unit_cost" placeholder=" "
                    required step="0.01" min="0">
                <label class="form-label">Unit Cost (₱) <span class="required-indicator">*</span></label>
                <div class="help-text">Original purchase cost for this batch (read-only)</div>
            </div>

            <div class="form-group">
                <input type="number" class="form-input" name="sale_price" id="price_sale_price" placeholder=" "
                    required step="0.01" min="0">
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
