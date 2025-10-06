<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Products - MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/products.css') }}">
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

    @if (session('success'))
        <div class="alert alert-success" id="flashMessage">
            {{ session('success') }}
        </div>
    @endif

    <div class="container fade-in" id="mainContent">
        <div class="header-bar">
            <h2 class="page-title">Product Management</h2>
            <div class="header-actions">
                <button class="btn btn-create" onclick="openModal()">Add New Product</button>
            </div>
        </div>

        <div class="table-wrapper" style="overflow-x: auto; max-height: 80vh;">
            <table class="inventory-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th>Form</th>
                        <th>Dosage</th>
                        <th>Total Stock</th>
                        <th>Batches</th>
                        <th>Earliest Expiry</th>
                        <th>Supplier</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            $totalStock = $product->batches->sum('quantity_remaining') ?? 0;
                            $isLowStock = $product->reorder_level && $totalStock <= $product->reorder_level;
                        @endphp
                        <tr class="{{ $isLowStock ? 'low-stock' : '' }}">
                            <td>{{ $product->product_code }}</td>
                            <td>
                                {{ $product->product_name }}
                                @if ($product->hasExpiredBatches())
                                    <span class="badge badge-danger">{{ $product->getExpiredBatchesCount() }}
                                        Expired</span>
                                @endif
                            </td>
                            <td>{{ $product->product_type }}</td>
                            <td>{{ $product->form_type }}</td>
                            <td>{{ $product->dosage_unit }}</td>
                            <td>
                                <span class="{{ $isLowStock ? 'text-red-600 font-bold' : '' }}">
                                    {{ number_format($totalStock) }}
                                </span>
                                @if ($product->reorder_level)
                                    <small class="text-gray-500">(Min: {{ $product->reorder_level }})</small>
                                @endif
                            </td>
                            <td>{{ $product->batches->count() ?? 0 }}</td>
                            <td>
                                @php
                                    $earliestBatch = $product->batches
                                        ->where('quantity_remaining', '>', 0)
                                        ->sortBy('expiration_date')
                                        ->first();
                                @endphp
                                @if ($earliestBatch)
                                    @php
                                        $daysUntilExpiry = now()->diffInDays($earliestBatch->expiration_date, false);
                                    @endphp
                                    <span
                                        class="{{ $daysUntilExpiry <= 30 ? 'text-orange-600' : ($daysUntilExpiry <= 7 ? 'text-red-600' : '') }}">
                                        {{ \Carbon\Carbon::parse($earliestBatch->expiration_date)->format('Y-m-d') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">No stock</span>
                                @endif
                            </td>
                            <td>{{ $product->supplier->name ?? '-' }}</td>
                            <td>
                                <div class="dropdown-container">
                                    <button class="dropdown-toggle" onclick="toggleDropdown(event)">&#8943;</button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item"
                                            onclick="showProductInfo({{ $product->id }})">Product Information</button>
                                        <button class="dropdown-item" onclick="showBatches({{ $product->id }})">View
                                            Batches</button>
                                        <button class="dropdown-item" onclick="editProduct({{ $product->id }})">Edit
                                            Product</button>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this product and all its batches?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-gray-500 py-4">No products found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal-bg add-modal" id="productModal">
        <div class="modal fade-in">
            <div class="modal-close" onclick="closeModal()">&times;</div>
            <div class="modal-header" id="modalTitle">Add New Product</div>
            <form method="POST" id="productForm" action="{{ route('products.store') }}" class="form-container"
                style="padding: 0 24px 24px 24px;">
                @csrf
                <input type="hidden" name="product_id" id="product_id" value="">

                <!-- Basic Product Information -->
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon">📦</span>
                        Basic Product Information
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <input type="text" class="form-input" name="product_name" id="product_name"
                                placeholder=" " required value="{{ old('product_name') }}">
                            <label for="product_name" class="form-label">Product Name <span
                                    class="required-indicator">*</span></label>
                            <div class="help-text">Enter the complete product name as it appears on packaging</div>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-input" name="generic_name" id="generic_name"
                                placeholder=" " value="{{ old('generic_name') }}" list="generic_names_list">
                            <label for="generic_name" class="form-label">Generic Name</label>
                            <div class="help-text">Active pharmaceutical ingredient (API) name (e.g., Paracetamol,
                                Ibuprofen)</div>
                            <datalist id="generic_names_list">
                                <option value="Paracetamol">
                                <option value="Ibuprofen">
                                <option value="Aspirin">
                                <option value="Amoxicillin">
                                <option value="Cetirizine">
                                <option value="Loratadine">
                                <option value="Metformin">
                                <option value="Omeprazole">
                                <option value="Simvastatin">
                                <option value="Amlodipine">
                                <option value="Losartan">
                                <option value="Atorvastatin">
                                <option value="Salbutamol">
                                <option value="Prednisolone">
                                <option value="Diclofenac">
                                <option value="Dextromethorphan">
                                <option value="Chlorpheniramine">
                                <option value="Phenylephrine">
                                <option value="Ascorbic Acid">
                                <option value="Calcium Carbonate">
                            </datalist>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-input" name="brand_name" id="brand_name"
                                placeholder=" " value="{{ old('brand_name') }}">
                            <label for="brand_name" class="form-label">Brand Name</label>
                            <div class="help-text">Commercial brand or trade name (Optional).</div>
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="manufacturer" id="manufacturer" required>
                                <option value="">Select Manufacturer</option>
                                <option value="Pfizer Inc.">Pfizer Inc.</option>
                                <option value="Johnson & Johnson">Johnson & Johnson</option>
                                <option value="GlaxoSmithKline">GlaxoSmithKline</option>
                                <option value="Novartis AG">Novartis AG</option>
                                <option value="Merck & Co.">Merck & Co.</option>
                                <option value="AbbVie Inc.">AbbVie Inc.</option>
                                <option value="Bristol-Myers Squibb">Bristol-Myers Squibb</option>
                                <option value="AstraZeneca">AstraZeneca</option>
                                <option value="Sanofi S.A.">Sanofi S.A.</option>
                                <option value="Roche Holding AG">Roche Holding AG</option>
                                <option value="United Laboratories (Unilab)">United Laboratories (Unilab)</option>
                                <option value="Zuellig Pharma">Zuellig Pharma</option>
                                <option value="Mercury Drug">Mercury Drug</option>
                                <option value="Pascual Laboratories">Pascual Laboratories</option>
                                <option value="Hizon Laboratories">Hizon Laboratories</option>
                                <option value="Other">Other</option>
                            </select>
                            <label for="manufacturer" class="form-label">Select Manufacturer<span
                                    class="required-indicator">*</span></label>
                        </div>
                    </div>
                </div>

                <!-- Medicine Classification -->
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon">🏥</span>
                        Medicine Classification
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <select class="form-select" name="product_type" id="product_type" required>
                                <option value="">Select Medicine Type</option>
                                <option value="Prescription">Prescription Medicine</option>
                                <option value="OTC">Over-the-Counter (OTC)</option>
                                <option value="Herbal">Herbal Medicine</option>
                                <option value="Food Supplement">Food Supplement</option>
                                <option value="Vitamins & Minerals">Vitamins & Minerals</option>
                                <option value="Medical Device">Medical Device</option>
                                <option value="Cosmeceutical">Cosmeceutical</option>
                                <option value="Veterinary">Veterinary Medicine</option>
                            </select>
                            <label for="product_type" class="form-label"> Select Medicine Type <span
                                    class="required-indicator">*</span></label>
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="classification" id="classification" required>
                                <option value="">Select Classification</option>
                                <option value="1">Antibiotic - Bacterial infections</option>
                                <option value="2">Analgesic - Pain relief</option>
                                <option value="3">Antipyretic - Fever reduction</option>
                                <option value="4">Anti-inflammatory - Inflammation</option>
                                <option value="5">Antacid - Stomach acid neutralizer</option>
                                <option value="6">Antihistamine - Allergic reactions</option>
                                <option value="7">Antihypertensive - High blood pressure</option>
                                <option value="8">Antidiabetic - Diabetes management</option>
                                <option value="9">Cardiovascular - Heart conditions</option>
                                <option value="10">Respiratory - Breathing disorders</option>
                                <option value="11">Gastrointestinal - Digestive system</option>
                                <option value="12">Dermatological - Skin conditions</option>
                                <option value="13">Neurological - Nervous system</option>
                                <option value="14">Psychiatric - Mental health</option>
                                <option value="15">Hormonal - Endocrine system</option>
                                <option value="16">Vitamin - Nutritional supplement</option>
                                <option value="17">Mineral - Essential minerals</option>
                                <option value="18">Immunosuppressant - Immune system</option>
                                <option value="19">Anticoagulant - Blood thinner</option>
                                <option value="20">Antifungal - Fungal infections</option>
                                <option value="21">Antiviral - Viral infections</option>
                                <option value="22">Other</option>
                            </select>
                            <label for="classification" class="form-label">Therapeutic Classification <span
                                    class="required-indicator">*</span></label>
                        </div>
                    </div>
                </div>

                <!-- Packaging & Units -->
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon">📦</span>
                        Packaging & Units
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <select class="form-select" name="unit" id="unit" required>
                                <option value="">Select Packaging Unit</option>

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
                                    <option value="piece" selected>Piece (individual items)</option>
                                    <option value="pack">Pack (multi-item)</option>
                                </optgroup>
                            </select>
                            <label for="unit" class="form-label">Packaging Unit <span
                                    class="required-indicator">*</span></label>
                            <div class="help-text">How is this product packaged? This is NOT the dosage form.</div>
                        </div>

                        <div class="form-group">
                            <input type="number" class="form-input" name="unit_quantity" id="unit_quantity"
                                placeholder=" " step="0.01" min="0.01" value="1" required>
                            <label for="unit_quantity" class="form-label">
                                <span id="unit_quantity_label">Contents per Package</span>
                            </label>
                            <div class="help-text" id="unit_quantity_help">
                                For 60mL bottle: enter 60. For 10-tablet blister: enter 10. For single tablet: enter 1
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <div class="unit-preview-box">
                                <label class="preview-label">Packaging Preview:</label>
                                <div id="unit-preview-display" class="preview-content">
                                    Select packaging unit above
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dosage and Formulation -->
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon">💊</span>
                        Dosage and Formulation
                    </h2>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <div class="dosage-container">
                                <div class="dosage-input-row">
                                    <div class="form-group">
                                        <select class="form-select" name="form_type" id="form_type" required>
                                            <option value="">Select Dosage Form</option>
                                            <optgroup label="Solid Dosage Forms">
                                                <option value="Tablet">Tablet</option>
                                                <option value="Capsule">Capsule</option>
                                                <option value="Caplet">Caplet</option>
                                                <option value="Powder">Powder</option>
                                                <option value="Granules">Granules</option>
                                                <option value="Chewable Tablet">Chewable Tablet</option>
                                                <option value="Extended Release">Extended Release Tablet</option>
                                                <option value="Enteric Coated">Enteric Coated Tablet</option>
                                            </optgroup>
                                            <optgroup label="Liquid Dosage Forms">
                                                <option value="Syrup">Syrup</option>
                                                <option value="Suspension">Suspension</option>
                                                <option value="Solution">Solution</option>
                                                <option value="Elixir">Elixir</option>
                                                <option value="Drops">Drops</option>
                                                <option value="Injection">Injection</option>
                                                <option value="IV Solution">IV Solution</option>
                                            </optgroup>
                                            <optgroup label="Topical Forms">
                                                <option value="Cream">Cream</option>
                                                <option value="Ointment">Ointment</option>
                                                <option value="Gel">Gel</option>
                                                <option value="Lotion">Lotion</option>
                                                <option value="Patch">Patch</option>
                                                <option value="Foam">Foam</option>
                                            </optgroup>
                                            <optgroup label="Other Forms">
                                                <option value="Inhaler">Inhaler</option>
                                                <option value="Nasal Spray">Nasal Spray</option>
                                                <option value="Eye Drops">Eye Drops</option>
                                                <option value="Suppository">Suppository</option>
                                            </optgroup>
                                        </select>
                                        <label for="form_type" class="form-label">Select Dosage Form <span
                                                class="required-indicator">*</span></label>
                                    </div>
                                    <br>
                                    <div class="form-group dosage-strength-group">
                                        <input type="text" class="form-input" name="dosage_strength"
                                            id="dosage_strength" placeholder=" "
                                            value="{{ old('dosage_strength') }}">
                                        <label for="dosage_strength" class="form-label">Dosage Strength</label>
                                    </div>
                                    <div class="form-group dosage-unit-group">
                                        <select class="form-select" name="dosage_unit" id="dosage_unit" required>
                                            <option value="">Unit</option>
                                            <option value="mg">mg (milligram)</option>
                                            <option value="g">g (gram)</option>
                                            <option value="mcg">mcg (microgram)</option>
                                            <option value="IU">IU (International Unit)</option>
                                            <option value="mL">mL (milliliter)</option>
                                            <option value="L">L (liter)</option>
                                            <option value="%">% (percentage)</option>
                                            <option value="mg/ml">mg/ml</option>
                                            <option value="mg/5ml">mg/5ml</option>
                                            <option value="drops">drops</option>
                                            <option value="ratio">ratio</option>
                                        </select>
                                        <label for="dosage_unit" class="form-label">Unit
                                            <span class="required-indicator">*</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="dosage-preview">
                                    <div class="dosage-preview-box">
                                        <span id="dosage-preview-text">Enter strength and unit above</span>
                                    </div>
                                </div>
                                <div class="help-text">e.g., 500mg, 250mg/5mL, 1%, 1:1000</div>
                            </div>
                        </div>

                    </div>

                </div>


                <!-- Storage and Handling -->
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon">🌡️</span>
                        Storage and Handling
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <select class="form-select" name="storage_requirements" id="storage_requirements">
                                <option value="">Select Storage Requirements</option>
                                <option value="Room Temperature">Room Temperature (15-30°C)</option>
                                <option value="Cool Place">Cool Place (8-15°C)</option>
                                <option value="Refrigerated">Refrigerated (2-8°C)</option>
                                <option value="Frozen">Frozen (-20°C or below)</option>
                                <option value="Protect from Light">Protect from Light</option>
                                <option value="Dry Place">Store in Dry Place</option>
                                <option value="Controlled Temperature">Controlled Room Temperature (20-25°C)</option>
                                <option value="Do Not Freeze">Do Not Freeze</option>
                                <option value="Store Upright">Store Upright</option>
                                <option value="Special Handling">Special Handling Required</option>
                            </select>
                            <label for="storage_requirements" class="form-label">Storage Requirements</label>
                        </div>
                    </div>
                </div>



                <!-- Inventory Management -->
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon">📊</span>
                        Inventory Management
                    </h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <input type="number" class="form-input" name="reorder_level" id="reorder_level"
                                placeholder=" " required min="0" value="{{ old('reorder_level') }}">
                            <label for="reorder_level" class="form-label">Reorder Level <span
                                    class="required-indicator">*</span></label>
                            <div class="help-text">Minimum stock level before reordering</div>
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="supplier_id" id="supplier_id">
                                <option value="">Select Primary Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <label for="supplier_id" class="form-label">Primary Supplier</label>
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="category_id" id="category_id">
                                <option value="">Select Category</option>
                                @foreach (\App\Models\Category::orderBy('name')->get() as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}</option>
                                @endforeach
                            </select>
                            <label for="category_id" class="form-label">Product Category</label>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Information Modal -->
    <div class="modal-bg" id="productInfoModal">
        <div class="modal fade-in product-info-modal">
            <div class="modal-close" onclick="closeProductInfoModal()">&times;</div>
            <div class="modal-header" id="productInfoTitle">Product Information</div>
            <div class="product-info-grid" id="productInfoContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Batches View Modal -->
    <div class="modal-bg" id="batchesModal">
        <div class="modal fade-in" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-close" onclick="closeBatchesModal()">&times;</div>
            <div class="modal-header" id="batchesModalTitle">Product Batches</div>
            <div id="batchesContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Batch Pricing Update Modal -->
    <div class="modal-bg" id="pricingModal">
        <div class="modal fade-in" style="max-width: 500px;">
            <div class="modal-close" onclick="closePricingModal()">&times;</div>
            <div class="modal-header">Update Batch Pricing</div>
            <form id="pricingForm" onsubmit="handlePricingUpdate(event)">
                @csrf
                <input type="hidden" id="pricing_batch_id" name="batch_id">
                <div class="form-group">
                    <label>Batch Information</label>
                    <div class="batch-info-display">
                        <p><strong>Batch:</strong> <span id="pricing_batch_number"></span></p>
                        <p><strong>Current Unit Cost:</strong> ₱<span id="pricing_current_cost"></span></p>
                        <p><strong>Current Sale Price:</strong> ₱<span id="pricing_current_price"></span></p>
                        <p><strong>Current Margin:</strong> <span id="pricing_current_margin"></span>%</p>
                    </div>
                </div>
                <div class="form-group">
                    <input type="number" step="0.01" name="unit_cost" id="pricing_unit_cost" placeholder=" "
                        min="0">
                    <label for="pricing_unit_cost">New Unit Cost</label>
                </div>
                <div class="form-group">
                    <input type="number" step="0.01" name="sale_price" id="pricing_sale_price" placeholder=" "
                        min="0">
                    <label for="pricing_sale_price">New Sale Price</label>
                </div>
                <div class="form-group">
                    <input type="text" name="reason" id="pricing_reason" placeholder=" ">
                    <label for="pricing_reason">Reason for Change</label>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel1" onclick="closePricingModal()">Cancel</button>
                    <button type="submit" class="btn btn-create1">Update Pricing</button>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        // Unit display mappings - PACKAGING ONLY (not dosage forms)
        const unitDisplayMap = {
            'bottle': 'bottle',
            'vial': 'vial',
            'ampoule': 'ampoule',
            'dropper_bottle': 'dropper bottle',
            'nebule': 'nebule',
            'blister_pack': 'blister pack',
            'box': 'box',
            'strip': 'strip',
            'sachet': 'sachet',
            'syringe': 'pre-filled syringe',
            'tube': 'tube',
            'jar': 'jar',
            'topical_bottle': 'bottle',
            'inhaler': 'inhaler',
            'patch': 'patch',
            'suppository': 'suppository',
            'piece': 'piece',
            'pack': 'pack'
        };

        // Container units that hold volume/contents
        const containerUnits = ['bottle', 'vial', 'ampoule', 'dropper_bottle', 'nebule',
            'tube', 'jar', 'topical_bottle', 'syringe'
        ];

        // Multi-item packaging
        const multiItemUnits = ['blister_pack', 'strip', 'box', 'pack', 'sachet'];

        function updateUnitQuantityLabel() {
            const unitSelect = document.getElementById('unit');
            const quantityInput = document.getElementById('unit_quantity');
            const quantityLabel = document.getElementById('unit_quantity_label');
            const quantityHelp = document.getElementById('unit_quantity_help');

            if (!unitSelect || !quantityInput) return;

            const selectedUnit = unitSelect.value;
            const unitDisplay = unitDisplayMap[selectedUnit] || selectedUnit;

            if (!selectedUnit) {
                quantityLabel.innerHTML = 'Contents per Package';
                quantityHelp.textContent = 'Select packaging unit first';
                return;
            }

            if (containerUnits.includes(selectedUnit)) {
                quantityLabel.innerHTML = `Volume/Size per ${unitDisplay}`;
                quantityHelp.textContent = `Enter the volume in mL (e.g., 60 for a 60mL bottle)`;
                if (quantityInput.value === '1') {
                    quantityInput.value = '';
                }
            } else if (selectedUnit === 'box') {
                quantityLabel.innerHTML = `Total pieces per box`;
                quantityHelp.innerHTML =
                    `Enter TOTAL pieces in the box<br><small style="color: #059669;">Example: Box with 10 blister packs × 12 tablets = enter 120</small>`;
                if (quantityInput.value === '1') {
                    quantityInput.value = '';
                }
            } else if (multiItemUnits.includes(selectedUnit)) {
                quantityLabel.innerHTML = `Items per ${unitDisplay}`;
                quantityHelp.textContent = `How many pieces per ${unitDisplay}? (e.g., 10 tablets per blister)`;
                if (quantityInput.value === '1') {
                    quantityInput.value = '';
                }
            } else {
                quantityLabel.innerHTML = 'Quantity per Unit';
                quantityHelp.textContent = 'Usually 1 for individual items';
                if (!quantityInput.value || quantityInput.value === '') {
                    quantityInput.value = '1';
                }
            }

            updateUnitPreview();
        }

        function updateUnitPreview() {
            const unitSelect = document.getElementById('unit');
            const quantityInput = document.getElementById('unit_quantity');
            const formTypeSelect = document.getElementById('form_type');
            const previewDisplay = document.getElementById('unit-preview-display');

            if (!unitSelect || !quantityInput || !previewDisplay) return;

            const selectedUnit = unitSelect.value;
            const quantity = parseFloat(quantityInput.value) || 0;
            const formType = formTypeSelect ? formTypeSelect.value : '';
            const unitDisplay = unitDisplayMap[selectedUnit] || selectedUnit;

            if (!selectedUnit) {
                previewDisplay.textContent = 'Select packaging unit above';
                previewDisplay.style.color = '#6c757d';
                return;
            }

            if (quantity <= 0) {
                previewDisplay.textContent = `Enter quantity for ${unitDisplay}`;
                previewDisplay.style.color = '#6c757d';
                return;
            }

            let preview = '';

            if (containerUnits.includes(selectedUnit)) {
                preview = `Stock counted in: ${unitDisplay}s of ${quantity}mL each`;
                if (formType) {
                    preview += ` (contains ${formType.toLowerCase()})`;
                }
            } else if (multiItemUnits.includes(selectedUnit)) {
                // UPDATED: Better handling for nested packaging
                if (selectedUnit === 'box') {
                    preview = `Stock counted in: boxes of ${quantity} items each`;
                    if (formType) {
                        preview += ` (${formType.toLowerCase()}s)`;
                    }
                    preview += `\n💡 Tip: If box contains blister packs, enter total pieces per box`;
                } else {
                    preview = `Stock counted in: ${unitDisplay}s of ${quantity} pieces each`;
                    if (formType) {
                        preview += ` (${formType.toLowerCase()}s)`;
                    }
                }
            } else if (quantity === 1) {
                preview = `Stock counted per individual ${unitDisplay}`;
            } else {
                preview = `Stock counted in: ${unitDisplay}s containing ${quantity} units each`;
            }

            previewDisplay.textContent = preview;
            previewDisplay.style.color = '#28a745';
            previewDisplay.style.fontWeight = '500';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const unitSelect = document.getElementById('unit');
            const quantityInput = document.getElementById('unit_quantity');
            const formTypeSelect = document.getElementById('form_type');

            if (unitSelect) {
                unitSelect.addEventListener('change', updateUnitQuantityLabel);
            }

            if (quantityInput) {
                quantityInput.addEventListener('input', updateUnitPreview);
            }

            if (formTypeSelect) {
                formTypeSelect.addEventListener('change', updateUnitPreview);
            }

            // Initialize if editing
            if (unitSelect && unitSelect.value) {
                updateUnitQuantityLabel();
            }
        });

        window.productData = {
            @foreach ($products as $product)
                '{{ $product->id }}': {
                    id: {{ $product->id }},
                    product_code: '{{ $product->product_code }}',
                    product_name: '{{ $product->product_name }}',
                    generic_name: '{{ $product->generic_name ?? '' }}',
                    manufacturer: '{{ $product->manufacturer ?? '' }}',
                    brand_name: '{{ $product->brand_name ?? '' }}',
                    product_type: '{{ $product->product_type ?? '' }}',
                    dosage_unit: '{{ $product->dosage_unit ?? '' }}',
                    form_type: '{{ $product->form_type ?? '' }}',
                    classification: '{{ $product->classification ?? '' }}',
                    storage_requirements: '{{ $product->storage_requirements ?? '' }}',
                    reorder_level: {{ $product->reorder_level ?? 0 }},
                    supplier_id: {{ $product->supplier_id ?? 'null' }},
                    category_id: {{ $product->category_id ?? 'null' }},
                    unit: '{{ $product->unit ?? 'piece' }}', // ADD THIS
                    unit_quantity: {{ $product->unit_quantity ?? 1 }}, // ADD THIS
                    supplier: @if ($product->supplier)
                        {
                            name: '{{ $product->supplier->name }}'
                        }
                    @else
                        null
                    @endif ,
                    category: @if ($product->category)
                        {
                            name: '{{ $product->category->name }}'
                        }
                    @else
                        null
                    @endif ,
                    created_at: '{{ $product->created_at }}',
                    updated_at: '{{ $product->updated_at }}'
                }
                @if (!$loop->last)
                    ,
                @endif
            @endforeach
        };

        async function refreshProductData() {
            try {
                const response = await fetch('/api/products', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch products');
                }

                const products = await response.json();

                window.productData = {};
                products.forEach(product => {
                    window.productData[product.id] = product;
                });

                return products;
            } catch (error) {
                console.error('Error refreshing product data:', error);
                throw error;
            }
        }

        async function refreshProduct(productId) {
            try {
                const response = await fetch(`/api/products/${productId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch product');
                }

                const product = await response.json();
                window.productData[productId] = product;
                return product;
            } catch (error) {
                console.error('Error refreshing product:', error);
                return window.productData[productId];
            }
        }
    </script>

    <script>
        let currentBatchId = null;
        let maxQuantity = 0;
        let currentProductId = null;

        window.addEventListener("load", () => {
            const flash = document.getElementById("flashMessage");
            if (flash) {
                setTimeout(() => flash.style.display = "none", 3000);
            }
        });

        // Add this function to split combined dosage units
        function splitDosageUnit(combinedDosage) {
            if (!combinedDosage) {
                return {
                    strength: '',
                    unit: ''
                };
            }

            // Define common units to look for
            const units = ['mg/5ml', 'mg/ml', 'mg', 'g', 'mcg', 'IU', 'mL', 'L', '%', 'drops', 'ratio'];

            // Sort units by length (longest first) to match more specific units first
            const sortedUnits = units.sort((a, b) => b.length - a.length);

            for (let unit of sortedUnits) {
                if (combinedDosage.endsWith(unit)) {
                    const strength = combinedDosage.substring(0, combinedDosage.length - unit.length);
                    return {
                        strength: strength,
                        unit: unit
                    };
                }
            }

            // If no unit is found, check if it's all numeric (strength only)
            if (/^\d+\.?\d*$/.test(combinedDosage)) {
                return {
                    strength: combinedDosage,
                    unit: ''
                };
            }

            // If it's not numeric and no unit found, treat as unit only
            return {
                strength: '',
                unit: combinedDosage
            };
        }

        // Dosage preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const strengthInput = document.getElementById('dosage_strength');
            const unitSelect = document.getElementById('dosage_unit');
            const previewText = document.getElementById('dosage-preview-text');

            function updateDosagePreview() {
                const strength = strengthInput.value.trim();
                const unit = unitSelect.value;

                if (strength && unit) {
                    previewText.textContent = strength + unit;
                    previewText.className = 'preview-text combined';
                } else if (unit) {
                    previewText.textContent = unit;
                    previewText.className = 'preview-text unit-only';
                } else if (strength) {
                    previewText.textContent = strength + ' (select unit)';
                    previewText.className = 'preview-text incomplete';
                } else {
                    previewText.textContent = 'Enter strength and unit above';
                    previewText.className = 'preview-text empty';
                }
            }

            if (strengthInput && unitSelect && previewText) {
                strengthInput.addEventListener('input', updateDosagePreview);
                unitSelect.addEventListener('change', updateDosagePreview);
                updateDosagePreview(); // Initial update
            }
        });

        function toggleDropdown(event) {
            event.stopPropagation();
            const container = event.currentTarget.closest(".dropdown-container");
            const dropdown = container.querySelector(".dropdown-menu");
            const toggle = event.currentTarget;

            // Close all other dropdowns
            document.querySelectorAll(".dropdown-container").forEach(cont => {
                const menu = cont.querySelector(".dropdown-menu");
                if (cont !== container) {
                    menu.style.display = "none";
                    cont.classList.remove("active");
                }
            });

            if (dropdown.style.display === "block") {
                dropdown.style.display = "none";
                container.classList.remove("active");
            } else {
                // Position dropdown relative to viewport
                const rect = toggle.getBoundingClientRect();
                dropdown.style.left = (rect.left + rect.width / 2) + "px";
                dropdown.style.top = (rect.bottom + 5) + "px";
                dropdown.style.display = "block";
                container.classList.add("active");
            }
        }

        window.onclick = (event) => {
            // Close dropdowns when clicking outside
            if (!event.target.matches('.dropdown-toggle')) {
                document.querySelectorAll(".dropdown-menu").forEach(menu => {
                    menu.style.display = "none";
                });
                document.querySelectorAll(".dropdown-container").forEach(container => {
                    container.classList.remove("active");
                });
            }

            // Close modals when clicking on the modal background (not the modal content)
            // EXCLUDE the add product modal (add-modal class)
            const modals = document.querySelectorAll('.modal-bg:not(.add-modal)');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };

        const modal = document.getElementById("productModal");
        const form = document.getElementById("productForm");
        const modalTitle = document.getElementById("modalTitle");
        const submitBtn = document.getElementById("submitBtn");
        const batchesModal = document.getElementById("batchesModal");
        const productInfoModal = document.getElementById("productInfoModal");

        function openModal() {
            modal.style.display = "flex";
            modalTitle.textContent = "Add New Product";
            submitBtn.textContent = "Save Product";
            form.action = "{{ route('products.store') }}";
            form.method = "POST";
            form.reset();
            document.getElementById("product_id").value = "";
            removeMethodInput();

            // Reset dosage preview
            const previewText = document.getElementById('dosage-preview-text');
            if (previewText) {
                previewText.textContent = 'Enter strength and unit above';
                previewText.className = 'preview-text empty';
            }
        }

        function closeModal() {
            modal.style.display = "none";
        }


        document.addEventListener('DOMContentLoaded', function() {
            const productForm = document.getElementById('productForm');
            if (productForm) {
                productForm.addEventListener('submit', async function(event) {
                    event.preventDefault(); // Prevent immediate submission

                    // Collect debug data
                    const debugData = {
                        before: {},
                        after: {},
                        fields: {}
                    };

                    // BEFORE manipulation
                    const formDataBefore = new FormData(this);
                    for (let [key, value] of formDataBefore.entries()) {
                        debugData.before[key] = value;
                    }

                    // Handle dosage combination
                    const strengthInput = document.getElementById('dosage_strength');
                    const unitSelect = document.getElementById('dosage_unit');

                    if (strengthInput && unitSelect) {
                        const strength = strengthInput.value.trim();
                        const unit = unitSelect.value;

                        debugData.fields.dosage_strength = strength;
                        debugData.fields.dosage_unit_select = unit;

                        let combinedField = document.getElementById('combined_dosage_unit');
                        if (!combinedField) {
                            combinedField = document.createElement('input');
                            combinedField.type = 'hidden';
                            combinedField.id = 'combined_dosage_unit';
                            combinedField.name = 'dosage_unit';
                            productForm.appendChild(combinedField);
                        }

                        let combinedValue = '';
                        if (strength && unit) {
                            combinedValue = strength + unit;
                        } else if (unit && !strength) {
                            combinedValue = unit;
                        } else if (strength && !unit) {
                            combinedValue = strength;
                        }

                        combinedField.value = combinedValue;
                        debugData.fields.combined_dosage_unit = combinedValue;

                        strengthInput.removeAttribute('name');
                        unitSelect.removeAttribute('name');
                    }

                    // Check unit fields
                    const unitField = document.getElementById('unit');
                    const unitQuantityField = document.getElementById('unit_quantity');

                    debugData.fields.unit = unitField ? unitField.value : 'MISSING';
                    debugData.fields.unit_quantity = unitQuantityField ? unitQuantityField.value :
                        'MISSING';
                    debugData.fields.unit_has_name_attr = unitField ? (unitField.getAttribute('name') ||
                        'NO NAME ATTR') : 'FIELD MISSING';
                    debugData.fields.unit_quantity_has_name_attr = unitQuantityField ? (
                        unitQuantityField.getAttribute('name') || 'NO NAME ATTR') : 'FIELD MISSING';

                    // AFTER manipulation
                    const formDataAfter = new FormData(this);
                    for (let [key, value] of formDataAfter.entries()) {
                        debugData.after[key] = value;
                    }

                    // Send debug data to Laravel
                    try {
                        await fetch('/api/debug-log', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(debugData)
                        });
                    } catch (error) {
                        console.error('Failed to send debug data:', error);
                    }

                    // Now submit the form
                    this.submit();
                });
            }
        });

        function editProduct(productId) {
            const product = window.productData[productId];
            if (!product) {
                alert('Product data not found');
                return;
            }

            modal.style.display = "flex";
            modalTitle.textContent = "Edit Product";
            submitBtn.textContent = "Update Product";
            form.action = `/dashboard/products/${product.id}`;
            form.method = "POST";
            addMethodInput('PUT');

            // Split combined dosage unit back to strength and unit
            const dosageSplit = splitDosageUnit(product.dosage_unit);

            // Populate form fields
            document.getElementById("product_id").value = product.id;
            document.getElementById("product_name").value = product.product_name || '';
            document.getElementById("generic_name").value = product.generic_name || '';
            document.getElementById("brand_name").value = product.brand_name || '';
            document.getElementById("manufacturer").value = product.manufacturer || '';
            document.getElementById("supplier_id").value = product.supplier_id || '';
            document.getElementById("category_id").value = product.category_id || '';
            document.getElementById("product_type").value = product.product_type || '';
            document.getElementById("dosage_strength").value = dosageSplit.strength;
            document.getElementById("dosage_unit").value = dosageSplit.unit;
            document.getElementById("form_type").value = product.form_type || '';
            document.getElementById("classification").value = product.classification || '';
            document.getElementById("reorder_level").value = product.reorder_level || '';
            document.getElementById("storage_requirements").value = product.storage_requirements || '';

            // Update dosage preview after setting values
            const strengthInput = document.getElementById('dosage_strength');
            const unitSelect = document.getElementById('dosage_unit');
            const previewText = document.getElementById('dosage-preview-text');

            if (strengthInput && unitSelect && previewText) {
                const strength = strengthInput.value.trim();
                const unit = unitSelect.value;

                if (strength && unit) {
                    previewText.textContent = strength + unit;
                    previewText.className = 'preview-text combined';
                } else if (unit) {
                    previewText.textContent = unit;
                    previewText.className = 'preview-text unit-only';
                } else if (strength) {
                    previewText.textContent = strength + ' (select unit)';
                    previewText.className = 'preview-text incomplete';
                } else {
                    previewText.textContent = 'Enter strength and unit above';
                    previewText.className = 'preview-text empty';
                }
            }
            // Set unit fields
            document.getElementById("unit").value = product.unit || 'piece';
            document.getElementById("unit_quantity").value = product.unit_quantity || '1';
            updateUnitQuantityLabel();
        }

        function showProductInfo(productId) {
            const product = window.productData[productId];
            if (!product) {
                alert('Product information not found');
                return;
            }

            const modal = document.getElementById('productInfoModal');
            const title = document.getElementById('productInfoTitle');
            const content = document.getElementById('productInfoContent');

            title.textContent = `${product.product_name} - Product Information`;

            // Get classification name from the select options
            const classificationSelect = document.getElementById('classification');
            let classificationText = 'Not specified';
            if (product.classification) {
                const option = classificationSelect.querySelector(`option[value="${product.classification}"]`);
                if (option) {
                    classificationText = option.textContent;
                }
            }
            const unitDisplayMap = {
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

            const unitText = unitDisplayMap[product.unit] || product.unit || 'Piece';
            const unitQuantity = product.unit_quantity || 1;

            // Build unit description
            let unitDescription = unitText;
            const volumeUnits = ['bottle', 'ml', 'L', 'vial', 'ampoule', 'dropper_bottle',
                'nebule', 'tube', 'jar', 'topical_bottle', 'syringe',
                'injection_vial', 'injection_ampoule'
            ];

            if (volumeUnits.includes(product.unit) && unitQuantity != 1) {
                if (product.unit === 'ml' || product.unit === 'L') {
                    unitDescription = `${unitQuantity} ${unitText}`;
                } else {
                    unitDescription = `${unitText} (${unitQuantity}mL)`;
                }
            } else if (['blister_pack', 'strip', 'box', 'pack'].includes(product.unit) && unitQuantity != 1) {
                unitDescription = `${unitText} (${unitQuantity} pcs)`;
            } else if (unitQuantity != 1) {
                unitDescription = `${unitText} (${unitQuantity} units)`;
            }

            content.innerHTML = `
        <div class="info-section">
            <h3>Basic Information</h3>
            <div class="info-item">
                <span class="info-label">Product Code:</span>
                <span class="info-value">${product.product_code || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Product Name:</span>
                <span class="info-value">${product.product_name || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Generic Name:</span>
                <span class="info-value">${product.generic_name || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Brand Name:</span>
                <span class="info-value">${product.brand_name || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Manufacturer:</span>
                <span class="info-value">${product.manufacturer || '-'}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Classification</h3>
            <div class="info-item">
                <span class="info-label">Product Type:</span>
                <span class="info-value">${product.product_type || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Category:</span>
                <span class="info-value">${product.category ? product.category.name : '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Classification:</span>
                <span class="info-value">${classificationText}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Dosage & Form</h3>
            <div class="info-item">
                <span class="info-label">Form Type:</span>
                <span class="info-value">${product.form_type || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Dosage:</span>
                <span class="info-value">${product.dosage_unit || '-'}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Packaging & Units</h3>
            <div class="info-item">
                <span class="info-label">Unit Type:</span>
                <span class="info-value">${unitDescription}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Stock Counted In:</span>
                <span class="info-value">${unitText}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Storage & Supply</h3>
            <div class="info-item">
                <span class="info-label">Storage Requirements:</span>
                <span class="info-value">${product.storage_requirements || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Reorder Level:</span>
                <span class="info-value">${product.reorder_level ? product.reorder_level + ' ' + unitText.toLowerCase() : '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Supplier:</span>
                <span class="info-value">${product.supplier ? product.supplier.name : '-'}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>System Information</h3>
            <div class="info-item">
                <span class="info-label">Created:</span>
                <span class="info-value">${formatDate(product.created_at)}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Last Updated:</span>
                <span class="info-value">${formatDate(product.updated_at)}</span>
            </div>
        </div>
    `;

            modal.style.display = 'flex';
        }

        function closeProductInfoModal() {
            document.getElementById('productInfoModal').style.display = 'none';
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function showBatches(productId) {
            currentProductId = productId;
            batchesModal.style.display = "flex";

            fetch(`/dashboard/products/${productId}/batches`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('batchesContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('batchesContent').innerHTML =
                        '<div class="text-red-600 p-4">Error loading batches: ' + error.message + '</div>';
                });
        }

        function closeBatchesModal() {
            batchesModal.style.display = "none";
            currentProductId = null;
        }

        function openStockOutModal(batchId, batchNumber, availableStock, productName, unitCost, salePrice) {
            currentBatchId = batchId;
            maxQuantity = availableStock;

            document.getElementById('stock_out_batch_id').value = batchId;
            document.getElementById('stock_out_product_id').value = currentProductId;
            document.getElementById('product_display_name').textContent = productName;
            document.getElementById('batch_display_number').textContent = batchNumber;
            document.getElementById('batch_display_stock').textContent = availableStock.toLocaleString();
            document.getElementById('batch_display_cost').textContent = parseFloat(unitCost).toFixed(2);
            document.getElementById('batch_display_price').textContent = parseFloat(salePrice).toFixed(2);
            document.getElementById('max_quantity').textContent = availableStock.toLocaleString();
            document.getElementById('stock_out_quantity').max = availableStock;

            document.getElementById('stockOutForm').reset();
            document.getElementById('stock_out_batch_id').value = batchId;
            document.getElementById('stock_out_product_id').value = currentProductId;

            document.getElementById('stockOutModal').style.display = 'flex';
        }

        function closeStockOutModal() {
            document.getElementById('stockOutModal').style.display = 'none';
            currentBatchId = null;
            maxQuantity = 0;
        }

        function openPricingModal(batchId, batchNumber, unitCost, salePrice) {
            document.getElementById('pricing_batch_id').value = batchId;
            document.getElementById('pricing_batch_number').textContent = batchNumber;
            document.getElementById('pricing_current_cost').textContent = parseFloat(unitCost).toFixed(2);
            document.getElementById('pricing_current_price').textContent = parseFloat(salePrice).toFixed(2);

            const margin = unitCost > 0 ? (((salePrice - unitCost) / unitCost) * 100).toFixed(2) : '0.00';
            document.getElementById('pricing_current_margin').textContent = margin;

            document.getElementById('pricingForm').reset();
            document.getElementById('pricing_batch_id').value = batchId;

            document.getElementById('pricingModal').style.display = 'flex';
        }

        function closePricingModal() {
            document.getElementById('pricingModal').style.display = 'none';
        }

        function handleStockOut(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const quantity = parseInt(formData.get('stock_out'));

            if (quantity > maxQuantity) {
                alert(`Cannot remove ${quantity} units. Maximum available: ${maxQuantity}`);
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
                        closeStockOutModal();
                        if (currentProductId) {
                            showBatches(currentProductId);
                        }

                        alert(data.message || 'Stock removed successfully');
                    } else {
                        alert(data.message || 'Error removing stock');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing stock: ' + error.message);
                });
        }

        function handlePricingUpdate(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const batchId = formData.get('batch_id');

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                alert('CSRF token not found. Please refresh the page.');
                return;
            }

            formData.append('action', 'update_pricing');

            fetch(`/dashboard/products/batch-action/${batchId}`, {
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
                        closePricingModal();
                        if (currentProductId) {
                            showBatches(currentProductId);
                        }

                        alert(data.message);

                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert(data.message || 'Error updating pricing');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating pricing: ' + error.message);
                });
        }

        function addMethodInput(method) {
            removeMethodInput();
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = method;
            methodInput.id = 'methodInput';
            form.appendChild(methodInput);
        }

        function removeMethodInput() {
            const input = document.getElementById("methodInput");
            if (input) input.remove();
        }

        window.openStockOutModal = openStockOutModal;
        window.openPricingModal = openPricingModal;
        window.showBatches = showBatches;
    </script>
    @stack('scripts')
</body>
@include('admin.admin-footer')

</html>
