<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/uploads.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    @include('client.client-header')

    <div class="main-container">

        <div class="panel">
            <h2><i class="fas fa-upload"></i> Upload Your Document</h2>

            @php $prescriptions = $prescriptions ?? collect(); @endphp

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
                class="upload-form">
                @csrf

                <div class="form-section">
                    <div class="section-title">Order Type</div>
                    <div class="order-type-selector">
                        <label class="order-type-option" for="prescription">
                            <input type="radio" id="prescription" name="order_type" value="prescription"
                                {{ old('order_type', 'prescription') === 'prescription' ? 'checked' : '' }}>
                            <div class="order-type-icon"></div>
                            <div class="order-type-title">Prescription Upload</div>
                            <div class="order-type-description">Upload a doctor's prescription for validation and
                                processing</div>
                        </label>

                        <label class="order-type-option" for="online_order">
                            <input type="radio" id="online_order" name="order_type" value="online_order"
                                {{ old('order_type') === 'online_order' ? 'checked' : '' }}>
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
                            placeholder="e.g. 09123456789" value="{{ old('mobile_number') }}" />
                    </div>

                    <div class="form-group">
                        <label for="notes"> Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any additional information...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="prescription_file" id="file-label"><i class="fas fa-file-upload"></i> Upload
                            Document (JPG, PNG, PDF)</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="prescription_file" name="prescription_file"
                                accept=".jpg,.jpeg,.png,.pdf" required />
                        </div>
                        <small id="file-security-note" style="color: #666; margin-top: 4px; display: block;">
                            <i class="fas fa-shield-alt"></i> Your document will be securely encrypted and can only be
                            viewed by authorized pharmacy staff.
                        </small>
                        <div class="file-info-dynamic" id="file-info"></div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Order
                </button>
            </form>
        </div>

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
                <h3 class="modal-title">
                    Order Details
                </h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
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

            // Add action buttons
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

        // Close modal
        function closeModal() {
            document.getElementById('orderModal').classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Update UI based on selected order type
        function updateOrderTypeUI() {
            const prescriptionRadio = document.getElementById('prescription');
            const onlineOrderRadio = document.getElementById('online_order');
            const fileLabel = document.getElementById('file-label');
            const securityNote = document.getElementById('file-security-note');

            // Update visual selection
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

        // Filter functionality
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

                // Status filter
                if (statusFilter !== 'all' && rowStatus !== statusFilter) {
                    showRow = false;
                }

                // Type filter
                if (typeFilter !== 'all' && rowType !== typeFilter) {
                    showRow = false;
                }

                // Date filter
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

                // Search filter
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

            // Show/hide no results message and table
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

        // Clear all filters
        function clearFilters() {
            document.getElementById('status-filter').value = 'all';
            document.getElementById('type-filter').value = 'all';
            document.getElementById('date-filter').value = 'all';
            document.getElementById('order-search').value = '';
            filterOrders();
        }

        // View document function
        function viewDocument(prescriptionId) {
            // Open the document in a new tab/window
            const documentUrl = `{{ url('/prescription/document/') }}/${prescriptionId}`;
            window.open(documentUrl, '_blank');
        }

        // View QR function
        function viewQR(prescriptionId) {
            window.open(`{{ url('/prescription/qr/') }}/${prescriptionId}`, '_blank');
        }

        // Initialize UI on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateOrderTypeUI();

            // Add event listeners for order type changes
            document.querySelectorAll('input[name="order_type"]').forEach(radio => {
                radio.addEventListener('change', updateOrderTypeUI);
            });

            // Add event listeners for filters
            document.getElementById('status-filter').addEventListener('change', filterOrders);
            document.getElementById('type-filter').addEventListener('change', filterOrders);
            document.getElementById('date-filter').addEventListener('change', filterOrders);
            document.getElementById('order-search').addEventListener('input', filterOrders);
            document.getElementById('clear-filters').addEventListener('click', clearFilters);

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        });

        // File validation
        document.getElementById('prescription_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('file-info');

            if (file) {
                const fileSize = file.size;
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (fileSize > maxSize) {
                    alert('File size must be less than 5MB');
                    e.target.value = '';
                    fileInfo.style.display = 'none';
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only JPG, PNG, or PDF files');
                    e.target.value = '';
                    fileInfo.style.display = 'none';
                    return;
                }

                const fileName = file.name;
                const fileSizeKB = (fileSize / 1024).toFixed(1);

                fileInfo.innerHTML = `
                    <div class="file-preview">
                        <i class="fas fa-file-alt file-icon"></i>
                        <div class="file-details">
                            <strong>Selected:</strong> ${fileName}<br>
                            <strong>Size:</strong> ${fileSizeKB} KB<br>
                            <strong>Type:</strong> ${file.type}
                        </div>
                    </div>
                `;
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
        });

        function showTemporarySuccess(data) {
            const tempMessage = document.createElement('div');
            tempMessage.className = 'success-message-temp';

            let content = `<h3><i class="fas fa-check-circle"></i> ${data.message}</h3>`;

            if (data.qrImage) {
                content += `
            <p><strong>Scan this QR code at the pharmacy:</strong></p>
            <img src="${data.qrImage}" alt="QR Code" style="max-width: 200px; margin: 10px 0;">
        `;
            }

            if (data.qrLink) {
                content += `
            <p><strong>Order link:</strong></p>
            <p><a href="${data.qrLink}" target="_blank" style="color: #a7f3d0; text-decoration: underline;">${data.qrLink}</a></p>
        `;
            }

            tempMessage.innerHTML = content;
            document.body.appendChild(tempMessage);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                tempMessage.classList.add('fade-out');
                setTimeout(() => {
                    tempMessage.remove();
                }, 500);
            }, 5000);
        }

        // Auto-format mobile number input
        document.getElementById('mobile_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.length > 11) {
                value = value.substring(0, 11);
            }

            e.target.value = value;
        });
    </script>

    @stack('scripts')
</body>

</html>
