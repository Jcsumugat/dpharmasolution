<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/uploads.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Restriction Alert Styles */
        .restriction-alert {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: start;
            gap: 15px;
            animation: slideDown 0.3s ease-out;
        }

        .restriction-alert-icon {
            font-size: 28px;
            color: #dc2626;
            flex-shrink: 0;
        }

        .restriction-alert-content h4 {
            color: #991b1b;
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
        }

        .restriction-alert-content p {
            color: #7f1d1d;
            margin: 0;
            line-height: 1.6;
        }

        .restriction-duration {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            margin: 0 2px;
        }

        /* Disabled button styles */
        .btn-submit.disabled {
            background: #9ca3af !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            position: relative;
        }

        .btn-submit.disabled:hover {
            background: #9ca3af !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-submit.disabled::after {
            content: '🔒';
            position: absolute;
            right: 15px;
            font-size: 18px;
        }

        /* Form overlay for disabled state */
        .form-disabled-overlay {
            position: relative;
        }

        .form-disabled-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 1;
            pointer-events: none;
            opacity: 0.5;
        }

        /* Restriction modal */
        .restriction-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .restriction-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .restriction-modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: scaleIn 0.3s;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .restriction-modal-icon {
            font-size: 60px;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .restriction-modal-content h3 {
            color: #991b1b;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .restriction-modal-content p {
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .restriction-modal-close {
            background: #ef4444;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .restriction-modal-close:hover {
            background: #dc2626;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Warning state for duplicate uploads */
        .file-preview.warning {
            background: #fffbeb !important;
            border: 2px solid #f59e0b !important;
        }

        /* Confirmed duplicate state */
        .file-preview.confirmed {
            background: #fef3c7 !important;
            border: 2px solid #f59e0b !important;
        }
    </style>
</head>

<body>

    @include('client.client-header')

    <div class="main-container">

        <div class="panel">
            <h2><i class="fas fa-upload"></i> Upload Your Document</h2>

            @php
                $prescriptions = $prescriptions ?? collect();
                $customer = auth()->user();
                $isRestricted = $customer->status === 'restricted';
            @endphp

            @if ($isRestricted)
                <div class="restriction-alert">
                    <div class="restriction-alert-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="restriction-alert-content">
                        <h4><i class="fas fa-exclamation-triangle"></i> Account Restricted</h4>
                        <p>
                            Your account has been restricted and you cannot place orders at this time.
                            @if ($customer->auto_restore_at)
                                Your account will be automatically restored in
                                <span class="restriction-duration" id="restriction-timer"
                                    data-restore-time="{{ $customer->auto_restore_at }}">
                                    Calculating...
                                </span>
                            @else
                                Please contact the pharmacy for more information.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showTemporarySuccess({
                            message: `{{ session('success') }}`,
                            qrImage: `{{ session('qr_image') }}`,
                            qrLink: `{{ session('qr_link') }}`
                        });
                    });
                </script>
            @endif

            @if (session('warning'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showDuplicateAlert({
                            message: `{{ session('warning') }}`
                        });
                    });
                </script>
            @endif

            @if ($errors->any())
                <div class="error-list">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('prescription.upload') }}" method="POST" enctype="multipart/form-data"
                class="upload-form {{ $isRestricted ? 'form-disabled-overlay' : '' }}" id="upload-form">
                @csrf

                <div class="form-section">
                    <div class="section-title">Order Type</div>
                    <div class="order-type-selector">
                        <label class="order-type-option" for="prescription">
                            <input type="radio" id="prescription" name="order_type" value="prescription"
                                {{ old('order_type', 'prescription') === 'prescription' ? 'checked' : '' }}
                                {{ $isRestricted ? 'disabled' : '' }}>
                            <div class="order-type-icon"></div>
                            <div class="order-type-title">Prescription Upload</div>
                            <div class="order-type-description">Upload a doctor's prescription for validation and
                                processing</div>
                        </label>

                        <label class="order-type-option" for="online_order">
                            <input type="radio" id="online_order" name="order_type" value="online_order"
                                {{ old('order_type') === 'online_order' ? 'checked' : '' }}
                                {{ $isRestricted ? 'disabled' : '' }}>
                            <div class="order-type-icon"></div>
                            <div class="order-type-title">Non-Prescription Upload</div>
                            <div class="order-type-description">Upload a list of medicines you want to order directly
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title"> Order Details</div>
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number</label>
                        <input type="text" id="mobile_number" name="mobile_number" required
                            placeholder="e.g. 09123456789" value="{{ old('mobile_number') }}"
                            {{ $isRestricted ? 'disabled' : '' }} />
                    </div>

                    <div class="form-group">
                        <label for="notes"> Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any additional information..."
                            {{ $isRestricted ? 'disabled' : '' }}>{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="prescription_file" id="file-label"><i class="fas fa-file-upload"></i> Upload
                            Document (JPG, PNG, PDF)</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="prescription_file" name="prescription_file"
                                accept=".jpg,.jpeg,.png,.pdf" required {{ $isRestricted ? 'disabled' : '' }} />
                        </div>
                        <small id="file-security-note" style="color: #666; margin-top: 4px; display: block;">
                            <i class="fas fa-shield-alt"></i> Your document will be securely encrypted and can only be
                            viewed by authorized pharmacy staff.
                        </small>
                        <div class="file-info-dynamic" id="file-info"></div>
                    </div>
                </div>

                <button type="submit" class="btn-submit {{ $isRestricted ? 'disabled' : '' }}" id="submit-btn"
                    {{ $isRestricted ? 'disabled' : '' }}>
                    <i class="fas fa-paper-plane"></i>
                    {{ $isRestricted ? 'Order Submission Disabled' : 'Submit Order' }}
                </button>
            </form>
        </div>

        <!-- Rest of the order history panel remains the same -->
        <div class="panel">
            <div class="history-header">
                <h3><i class="fas fa-history"></i> Your Order History</h3>
                <div class="order-stats">
                    <div class="stat-item">
                        <span class="stat-number">{{ $prescriptions->count() }}</span>
                        <span class="stat-label">Total Orders</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">{{ $prescriptions->where('status', 'completed')->count() }}</span>
                        <span class="stat-label">Completed</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">{{ $prescriptions->where('status', 'cancelled')->count() }}</span>
                        <span class="stat-label">Cancelled</span>
                    </div>
                </div>
            </div>

            <div class="filters-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status-filter"><i class="fas fa-filter"></i> Filter by Status:</label>
                        <select id="status-filter" class="filter-select">
                            <option value="all">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="type-filter"><i class="fas fa-tags"></i> Filter by Type:</label>
                        <select id="type-filter" class="filter-select">
                            <option value="all">All Types</option>
                            <option value="prescription">Prescription</option>
                            <option value="online_order">Non-Prescription</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date-filter"><i class="fas fa-calendar"></i> Date Range:</label>
                        <select id="date-filter" class="filter-select">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="3months">Last 3 Months</option>
                        </select>
                    </div>
                </div>
                <div class="search-row">
                    <div class="search-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="order-search" class="search-input"
                            placeholder="Search by Order ID or notes...">
                    </div>
                    <button class="clear-filters-btn" id="clear-filters">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>

            <div class="table-container" id="table-container">
                @if ($prescriptions->count() > 0)
                    <table class="history-table" id="history-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prescriptions as $prescription)
                                <tr class="history-row"
                                    data-status="{{ strtolower($prescription->status ?? 'completed') }}"
                                    data-type="{{ $prescription->order_type ?? 'prescription' }}"
                                    data-date="{{ $prescription->created_at->format('Y-m-d') }}"
                                    data-search="{{ strtolower(($prescription->order->order_id ?? 'N/A') . ' ' . ($prescription->notes ?? '')) }}">
                                    <td class="order-id-cell">{{ $prescription->order->order_id ?? 'N/A' }}</td>
                                    <td>{{ $prescription->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <button class="action-btn"
                                            onclick="viewOrderDetails('{{ $prescription->id }}')">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div id="no-results" class="no-history" style="display: none;">
                <div class="no-history-icon"><i class="fas fa-search"></i></div>
                <h4>No orders match your filters</h4>
                <p>Try adjusting your search criteria or clear all filters to see all orders.</p>
            </div>

            @if ($prescriptions->count() === 0)
                <div class="no-history">
                    <div class="no-history-icon"><i class="fas fa-clipboard-list"></i></div>
                    <h4>No orders found</h4>
                    <p>You haven't uploaded any orders yet. Upload your first prescription or medicine list above!</p>
                </div>
            @endif
        </div>

    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Order Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Restriction Modal -->
    <div id="restrictionModal" class="restriction-modal">
        <div class="restriction-modal-content">
            <div class="restriction-modal-icon">
                <i class="fas fa-ban"></i>
            </div>
            <h3>Account Restricted</h3>
            <p id="restriction-message">
                Your account has been restricted and you cannot place orders until it's unrestricted.
            </p>
            <button class="restriction-modal-close" onclick="closeRestrictionModal()">
                Understood
            </button>
        </div>
    </div>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.19.0/js/md5.min.js"></script>
<script>
    // Restriction data from server
    const isRestricted = {{ $isRestricted ? 'true' : 'false' }};

    // Store prescription data for modal display
    const prescriptionsData = {
        @foreach ($prescriptions as $prescription)
            '{{ $prescription->id }}': {
                id: '{{ $prescription->id }}',
                orderId: '{{ $prescription->order->order_id ?? 'N/A' }}',
                orderType: '{{ $prescription->order_type ?? 'prescription' }}',
                status: '{{ $prescription->status ?? 'completed' }}',
                createdAt: '{{ $prescription->created_at->format('M d, Y') }}',
                createdAtTime: '{{ $prescription->created_at->format('h:i A') }}',
                timeAgo: '{{ $prescription->created_at->diffForHumans() }}',
                notes: {!! json_encode($prescription->notes ?? '') !!},
                adminMessage: {!! json_encode($prescription->admin_message ?? '') !!},
                originalFilename: {!! json_encode($prescription->original_filename ?? '') !!},
                fileSize: '{{ $prescription->file_size ? number_format($prescription->file_size / 1024, 1) . ' KB' : '' }}',
                isEncrypted: {{ $prescription->is_encrypted ? 'true' : 'false' }},
                hasFile: {{ $prescription->file_path || ($prescription->is_encrypted && $prescription->original_filename) ? 'true' : 'false' }},
                hasQrCode: {{ $prescription->qr_code_path ? 'true' : 'false' }}
            },
        @endforeach
    };

    // Global variables for file handling
    let selectedFileHash = null;
    let isDuplicateFile = false;
    let duplicateConfirmed = false;

    // Timer function for restriction countdown
    function updateRestrictionTimer() {
        const timerElement = document.getElementById('restriction-timer');
        if (!timerElement) return;

        const restoreTime = new Date(timerElement.dataset.restoreTime).getTime();
        const now = new Date().getTime();
        const distance = restoreTime - now;

        if (distance < 0) {
            timerElement.textContent = 'Restriction expired - Please refresh the page';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        let timeString = '';
        if (days > 0) {
            timeString = `${days} day${days > 1 ? 's' : ''}, ${hours} hour${hours > 1 ? 's' : ''}`;
        } else if (hours > 0) {
            timeString = `${hours} hour${hours > 1 ? 's' : ''}, ${minutes} minute${minutes > 1 ? 's' : ''}`;
        } else if (minutes > 0) {
            timeString = `${minutes} minute${minutes > 1 ? 's' : ''}, ${seconds} second${seconds > 1 ? 's' : ''}`;
        } else {
            timeString = `${seconds} second${seconds > 1 ? 's' : ''}`;
        }

        timerElement.textContent = timeString;
    }

    // Start the timer if restriction exists
    if (isRestricted) {
        updateRestrictionTimer();
        setInterval(updateRestrictionTimer, 1000);
    }

    // Handle button clicks for restricted accounts
    document.getElementById('submit-btn').addEventListener('click', function(e) {
        if (isRestricted) {
            e.preventDefault();
            showRestrictionModal();
            return false;
        }
    });

    function showRestrictionModal() {
        const modal = document.getElementById('restrictionModal');
        const message = document.getElementById('restriction-message');
        const timerElement = document.getElementById('restriction-timer');

        if (timerElement && timerElement.dataset.restoreTime) {
            const duration = timerElement.textContent;
            message.innerHTML =
                `Your account has been restricted for <strong>${duration}</strong> and you cannot place orders until it's unrestricted. Please wait or contact the pharmacy for more information.`;
        } else {
            message.innerHTML =
                'Your account has been restricted and you cannot place orders until it\'s unrestricted. Please contact the pharmacy for more information.';
        }

        modal.classList.add('show');
    }

    function closeRestrictionModal() {
        document.getElementById('restrictionModal').classList.remove('show');
    }

    // View order details in modal
    function viewOrderDetails(prescriptionId) {
        const data = prescriptionsData[prescriptionId];
        if (!data) return;

        const orderTypeLabel = data.orderType === 'online_order' ? 'Medicine List' : 'Prescription';
        const orderTypeIcon = data.orderType === 'online_order' ? 'fas fa-list' : 'fas fa-prescription-bottle-alt';
        const statusIcon = data.status === 'completed' ? 'fas fa-check-circle' :
            data.status === 'cancelled' ? 'fas fa-times-circle' : 'fas fa-clock';

        let modalContent = `
            <div class="detail-section">
                <h4 class="section-title"><i class="fas fa-info-circle"></i> Order Information</h4>
                <div class="detail-row">
                    <div class="detail-label">Order ID:</div>
                    <div class="detail-value"><strong>${data.orderId}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Order Type:</div>
                    <div class="detail-value">
                        <span class="order-type-badge ${data.orderType}">
                            <i class="${orderTypeIcon}"></i> ${orderTypeLabel}
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge ${data.status}">
                            <i class="${statusIcon}"></i> ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date Uploaded:</div>
                    <div class="detail-value">${data.createdAt} at ${data.createdAtTime}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Time Ago:</div>
                    <div class="detail-value">${data.timeAgo}</div>
                </div>
            </div>
        `;

        if (data.notes) {
            modalContent += `
                <div class="detail-section">
                    <h4 class="section-title"><i class="fas fa-sticky-note"></i> Notes</h4>
                    <div class="detail-row">
                        <div class="detail-value" style="width: 100%;">${data.notes}</div>
                    </div>
                </div>
            `;
        }

        if (data.adminMessage) {
            modalContent += `
                <div class="detail-section">
                    <div class="admin-message-section">
                        <h4 class="section-title" style="margin-bottom: 12px;">
                            <i class="fas fa-comment-medical" style="color: #f59e0b;"></i> Pharmacy Message
                        </h4>
                        <div class="admin-message-text">${data.adminMessage}</div>
                    </div>
                </div>
            `;
        }

        if (data.hasFile) {
            modalContent += `
                <div class="detail-section">
                    <h4 class="section-title"><i class="fas fa-file"></i> Document</h4>
                    <div class="file-info">
                        <div class="file-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <div><strong>${data.originalFilename || 'Document uploaded'}</strong></div>
                            ${data.fileSize ? `<div style="color: #64748b; font-size: 0.875rem;">Size: ${data.fileSize}</div>` : ''}
                            ${data.isEncrypted ? '<div class="security-badge"><i class="fas fa-shield-alt"></i> Encrypted</div>' : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        let actionButtons = '';
        if (data.hasFile) {
            actionButtons += `<button class="btn-primary" onclick="viewDocument('${data.id}')">
                <i class="fas fa-eye"></i> View Document
            </button>`;
        }
        if (data.hasQrCode) {
            actionButtons += `<button class="btn-primary" onclick="viewQR('${data.id}')">
                <i class="fas fa-qrcode"></i> View QR Code
            </button>`;
        }

        if (actionButtons) {
            modalContent += `
                <div class="modal-actions">
                    <button class="btn-secondary" onclick="closeModal()">Close</button>
                    ${actionButtons}
                </div>
            `;
        } else {
            modalContent += `
                <div class="modal-actions">
                    <button class="btn-secondary" onclick="closeModal()">Close</button>
                </div>
            `;
        }

        document.getElementById('modal-body-content').innerHTML = modalContent;
        document.getElementById('orderModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('orderModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('orderModal');
        const restrictionModal = document.getElementById('restrictionModal');

        if (event.target === modal) {
            closeModal();
        }
        if (event.target === restrictionModal) {
            closeRestrictionModal();
        }
    }

    function updateOrderTypeUI() {
        if (isRestricted) return;

        const prescriptionRadio = document.getElementById('prescription');
        const onlineOrderRadio = document.getElementById('online_order');
        const fileLabel = document.getElementById('file-label');
        const securityNote = document.getElementById('file-security-note');

        document.querySelectorAll('.order-type-option').forEach(option => {
            option.classList.remove('selected');
        });

        if (prescriptionRadio.checked) {
            prescriptionRadio.closest('.order-type-option').classList.add('selected');
            fileLabel.innerHTML = '<i class="fas fa-file-upload"></i> Upload Prescription (JPG, PNG, PDF)';
            securityNote.innerHTML =
                '<i class="fas fa-shield-alt"></i> Your prescription will be securely encrypted and can only be viewed by authorized pharmacy staff.';
        } else if (onlineOrderRadio.checked) {
            onlineOrderRadio.closest('.order-type-option').classList.add('selected');
            fileLabel.innerHTML = '<i class="fas fa-file-upload"></i> Upload Medicine List (JPG, PNG, PDF)';
            securityNote.innerHTML =
                '<i class="fas fa-shield-alt"></i> Your medicine list will be securely encrypted and processed by our pharmacy staff.';
        }
    }

    function filterOrders() {
        const statusFilter = document.getElementById('status-filter').value;
        const typeFilter = document.getElementById('type-filter').value;
        const dateFilter = document.getElementById('date-filter').value;
        const searchTerm = document.getElementById('order-search').value.toLowerCase();
        const rows = document.querySelectorAll('.history-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowStatus = row.dataset.status;
            const rowType = row.dataset.type;
            const rowDate = new Date(row.dataset.date);
            const rowSearch = row.dataset.search;
            const today = new Date();

            let showRow = true;

            if (statusFilter !== 'all' && rowStatus !== statusFilter) {
                showRow = false;
            }

            if (typeFilter !== 'all' && rowType !== typeFilter) {
                showRow = false;
            }

            if (dateFilter !== 'all') {
                const daysDiff = Math.floor((today - rowDate) / (1000 * 60 * 60 * 24));

                switch (dateFilter) {
                    case 'today':
                        if (daysDiff > 0) showRow = false;
                        break;
                    case 'week':
                        if (daysDiff > 7) showRow = false;
                        break;
                    case 'month':
                        if (daysDiff > 30) showRow = false;
                        break;
                    case '3months':
                        if (daysDiff > 90) showRow = false;
                        break;
                }
            }

            if (searchTerm && !rowSearch.includes(searchTerm)) {
                showRow = false;
            }

            if (showRow) {
                row.style.display = 'table-row';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const noResults = document.getElementById('no-results');
        const tableContainer = document.getElementById('table-container');
        const hasRows = rows.length > 0;

        if (visibleCount === 0 && hasRows) {
            noResults.style.display = 'block';
            tableContainer.classList.add('hidden');
        } else {
            noResults.style.display = 'none';
            tableContainer.classList.remove('hidden');
        }
    }

    function clearFilters() {
        document.getElementById('status-filter').value = 'all';
        document.getElementById('type-filter').value = 'all';
        document.getElementById('date-filter').value = 'all';
        document.getElementById('order-search').value = '';
        filterOrders();
    }

    function viewDocument(prescriptionId) {
        const documentUrl = `{{ url('/prescription/document/') }}/${prescriptionId}`;
        window.open(documentUrl, '_blank');
    }

    function viewQR(prescriptionId) {
        window.open(`{{ url('/prescription/qr/') }}/${prescriptionId}`, '_blank');
    }

    function calculateFileHash(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const arrayBuffer = e.target.result;
                    const uint8Array = new Uint8Array(arrayBuffer);

                    console.log('File info:', {
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        lastModified: file.lastModified,
                        arrayBufferSize: arrayBuffer.byteLength
                    });

                    let binaryString = '';
                    for (let i = 0; i < uint8Array.length; i++) {
                        binaryString += String.fromCharCode(uint8Array[i]);
                    }

                    const hash = md5(binaryString);
                    console.log('MD5 hash calculated:', hash);
                    console.log('First 100 bytes:', Array.from(uint8Array.slice(0, 100)));
                    resolve(hash);
                } catch (error) {
                    console.error('Hash calculation error:', error);
                    reject(error);
                }
            };
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    }

    // File input change handler
    document.getElementById('prescription_file').addEventListener('change', async function(e) {
        if (isRestricted) {
            e.preventDefault();
            showRestrictionModal();
            this.value = '';
            return;
        }

        const file = e.target.files[0];
        const fileInfo = document.getElementById('file-info');
        const submitBtn = document.getElementById('submit-btn');

        // Reset state
        duplicateConfirmed = false;
        isDuplicateFile = false;
        selectedFileHash = null;

        if (file) {
            const fileSize = file.size;
            const maxSize = 5 * 1024 * 1024;

            if (fileSize > maxSize) {
                alert('File size must be less than 5MB');
                e.target.value = '';
                fileInfo.style.display = 'none';
                submitBtn.disabled = true;
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please upload only JPG, PNG, or PDF files');
                e.target.value = '';
                fileInfo.style.display = 'none';
                submitBtn.disabled = true;
                return;
            }

            fileInfo.innerHTML = `
                <div class="file-preview" style="background: #f0f9ff; border: 2px dashed #0284c7; padding: 15px; border-radius: 8px;">
                    <i class="fas fa-spinner fa-spin" style="color: #0284c7; font-size: 24px;"></i>
                    <div class="file-details" style="margin-left: 15px;">
                        <strong>Checking for duplicates...</strong><br>
                        <small>Please wait while we verify this file.</small>
                    </div>
                </div>
            `;
            fileInfo.style.display = 'block';
            submitBtn.disabled = true;

            try {
                console.log('Starting hash calculation...');
                const hash = await calculateFileHash(file);
                selectedFileHash = hash;

                console.log('Hash calculated:', hash);
                console.log('Sending API request to check duplicate...');

                const response = await fetch('/prescription/quick-duplicate-check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        file_hash: hash
                    })
                });

                console.log('API Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('API Response data:', result);

                if (result.is_duplicate) {
                    console.log('Duplicate detected!');
                    isDuplicateFile = true;
                    showDuplicateWarning(file, fileSize, result.message, result.details);
                } else {
                    console.log('File is unique');
                    isDuplicateFile = false;
                    showFileSuccess(file, fileSize);
                }
            } catch (error) {
                console.error('Duplicate check failed:', error);
                isDuplicateFile = false;
                showFileSuccess(file, fileSize);
            }
        } else {
            fileInfo.style.display = 'none';
            selectedFileHash = null;
            isDuplicateFile = false;
            duplicateConfirmed = false;
            submitBtn.disabled = true;
        }
    });

    function showDuplicateWarning(file, fileSize, message, details) {
        const fileInfo = document.getElementById('file-info');
        const submitBtn = document.getElementById('submit-btn');
        const fileName = file.name;
        const fileSizeKB = (fileSize / 1024).toFixed(1);

        const warningHTML = `
            <div class="file-preview warning" style="background: #fffbeb; border: 2px solid #f59e0b; padding: 15px; border-radius: 8px;">
                <i class="fas fa-exclamation-triangle" style="color: #d97706; font-size: 24px;"></i>
                <div class="file-details" style="margin-left: 15px;">
                    <strong style="color: #92400e;">⚠️ Duplicate Detected</strong><br>
                    <span style="color: #78350f;">${message}</span><br><br>
                    <strong>Current File:</strong> ${fileName}<br>
                    <strong>Size:</strong> ${fileSizeKB} KB<br><br>
                    <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-top: 10px; border-left: 4px solid #f59e0b;">
                        <strong style="color: #92400e;">You can still upload this file</strong><br>
                        <small style="color: #78350f;">
                            Our pharmacy staff will review it to verify if it's a legitimate reorder or duplicate submission.
                            Click "Upload Anyway" to proceed.
                        </small>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button type="button" class="btn-upload-anyway"
                                style="background: #f59e0b; color: white; border: none; padding: 12px 24px;
                                       border-radius: 6px; cursor: pointer; font-weight: 600; flex: 1;
                                       transition: background 0.2s;">
                            <i class="fas fa-check"></i> Upload Anyway
                        </button>
                        <button type="button" class="btn-cancel-upload"
                                style="background: #6b7280; color: white; border: none; padding: 12px 24px;
                                       border-radius: 6px; cursor: pointer; flex: 1;
                                       transition: background 0.2s;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        `;

        fileInfo.innerHTML = warningHTML;
        fileInfo.style.display = 'block';
        submitBtn.disabled = true;

        const uploadAnywayBtn = fileInfo.querySelector('.btn-upload-anyway');
        const cancelBtn = fileInfo.querySelector('.btn-cancel-upload');

        uploadAnywayBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            confirmDuplicateUpload();
        });

        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            cancelUpload();
        });
    }

    function showFileSuccess(file, fileSize) {
        const fileInfo = document.getElementById('file-info');
        const submitBtn = document.getElementById('submit-btn');
        const fileName = file.name;
        const fileSizeKB = (fileSize / 1024).toFixed(1);

        fileInfo.innerHTML = `
            <div class="file-preview" style="background: #f0fdf4; border: 2px solid #10b981; padding: 15px; border-radius: 8px;">
                <i class="fas fa-check-circle" style="color: #059669; font-size: 24px;"></i>
                <div class="file-details" style="margin-left: 15px;">
                    <strong style="color: #065f46;">✓ File Verified</strong><br>
                    <span style="color: #047857;">No duplicates found. Ready to upload.</span><br><br>
                    <strong>Selected:</strong> ${fileName}<br>
                    <strong>Size:</strong> ${fileSizeKB} KB<br>
                    <strong>Type:</strong> ${file.type}
                </div>
            </div>
        `;
        fileInfo.style.display = 'block';

        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }

    function confirmDuplicateUpload() {
        duplicateConfirmed = true;
        const fileInfo = document.getElementById('file-info');
        const submitBtn = document.getElementById('submit-btn');

        const fileInput = document.getElementById('prescription_file');
        const file = fileInput.files[0];
        const fileName = file.name;
        const fileSizeKB = (file.size / 1024).toFixed(1);

        fileInfo.innerHTML = `
            <div class="file-preview" style="background: #fef3c7; border: 2px solid #f59e0b; padding: 15px; border-radius: 8px;">
                <i class="fas fa-exclamation-circle" style="color: #d97706; font-size: 24px;"></i>
                <div class="file-details" style="margin-left: 15px;">
                    <strong style="color: #92400e;">⚠️ Duplicate Upload Confirmed</strong><br>
                    <span style="color: #78350f;">You've chosen to proceed with this duplicate file. Our pharmacy will review it.</span><br><br>
                    <strong>File:</strong> ${fileName}<br>
                    <strong>Size:</strong> ${fileSizeKB} KB<br>
                    <strong>Status:</strong> <span style="color: #d97706; font-weight: 600;">Ready for review</span>
                </div>
            </div>
        `;

        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }

    function cancelUpload() {
        document.getElementById('prescription_file').value = '';
        document.getElementById('file-info').style.display = 'none';
        selectedFileHash = null;
        isDuplicateFile = false;
        duplicateConfirmed = false;

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
    }

    // CRITICAL FIX: Form submission handler that sends the hash
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        if (isRestricted) {
            e.preventDefault();
            showRestrictionModal();
            return false;
        }

        const fileInput = document.getElementById('prescription_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            alert('Please select a file to upload.');
            return false;
        }

        if (isDuplicateFile && !duplicateConfirmed) {
            e.preventDefault();
            alert('⚠️ Please confirm the duplicate upload by clicking "Upload Anyway" button.');
            return false;
        }

        // CRITICAL: Add the file hash as a hidden input
        if (selectedFileHash) {
            // Remove any existing file_hash input to avoid duplicates
            const existingHashInput = this.querySelector('input[name="file_hash"]');
            if (existingHashInput) {
                existingHashInput.remove();
            }

            // Add new hash input
            const hashInput = document.createElement('input');
            hashInput.type = 'hidden';
            hashInput.name = 'file_hash';
            hashInput.value = selectedFileHash;
            this.appendChild(hashInput);

            console.log('✓ Submitting form with file hash:', selectedFileHash);
        } else {
            console.warn('⚠ No file hash available - backend will calculate it');
        }

        return true;
    });

    function showTemporarySuccess(data) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s;
        `;

        const messageBox = document.createElement('div');
        messageBox.style.cssText = `
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
        `;

        let content = `
            <div style="font-size: 48px; margin-bottom: 15px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="margin: 0 0 15px 0; font-size: 24px;">${data.message}</h3>
        `;

        if (data.qrImage) {
            content += `
                <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <p style="color: #374151; margin: 0 0 10px 0; font-weight: 600;">Scan this QR code at the pharmacy:</p>
                    <img src="${data.qrImage}" alt="QR Code" style="max-width: 200px; margin: 0 auto; display: block;">
                </div>
            `;
        }

        content += `
            <button onclick="this.closest('div[style*=fixed]').remove()"
                style="background: white; color: #059669; border: none; padding: 12px 30px;
                       border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;
                       margin-top: 15px; transition: transform 0.2s;">
                <i class="fas fa-times"></i> Close
            </button>
        `;

        if (data.qrLink) {
            content += `
                <p><strong>Order link:</strong></p>
                <p><a href="${data.qrLink}" target="_blank" style="color: #a7f3d0; text-decoration: underline;">${data.qrLink}</a></p>
            `;
        }

        messageBox.innerHTML = content;
        overlay.appendChild(messageBox);
        document.body.appendChild(overlay);

        setTimeout(() => {
            overlay.style.animation = 'fadeOut 0.3s';
            setTimeout(() => overlay.remove(), 300);
        }, 8000);

        setTimeout(() => {
            window.location.reload();
        }, 8500);
    }

    function showDuplicateAlert(data) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s;
        `;

        const messageBox = document.createElement('div');
        messageBox.style.cssText = `
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            border: 3px solid #ef4444;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
        `;

        messageBox.innerHTML = `
            <div style="font-size: 48px; margin-bottom: 15px; color: #dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin: 0 0 15px 0; font-size: 24px; color: #991b1b;">Duplicate Detected!</h3>
            <p style="color: #7f1d1d; line-height: 1.6;">${data.message}</p>
            <button onclick="this.closest('div[style*=fixed]').remove()"
                style="background: #dc2626; color: white; border: none; padding: 12px 30px;
                       border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;
                       margin-top: 15px; transition: transform 0.2s;">
                <i class="fas fa-times"></i> Close
            </button>
        `;

        overlay.appendChild(messageBox);
        document.body.appendChild(overlay);

        setTimeout(() => {
            overlay.style.animation = 'fadeOut 0.3s';
            setTimeout(() => overlay.remove(), 300);
        }, 10000);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateOrderTypeUI();

        if (!isRestricted) {
            document.querySelectorAll('input[name="order_type"]').forEach(radio => {
                radio.addEventListener('change', updateOrderTypeUI);
            });
        }

        document.getElementById('status-filter').addEventListener('change', filterOrders);
        document.getElementById('type-filter').addEventListener('change', filterOrders);
        document.getElementById('date-filter').addEventListener('change', filterOrders);
        document.getElementById('order-search').addEventListener('input', filterOrders);
        document.getElementById('clear-filters').addEventListener('click', clearFilters);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeRestrictionModal();
            }
        });
    });
</script>

    @stack('scripts')
</body>

</html>
