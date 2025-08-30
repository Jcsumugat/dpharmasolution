<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/uploads.css') }}">
    <style>
        .encrypted-file-info {
            background: #f0f8ff;
            border: 1px solid #4a90e2;
            border-radius: 4px;
            padding: 8px;
            margin: 4px 0;
            font-size: 0.9em;
        }
        .security-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .file-size {
            color: #666;
            font-size: 0.85em;
        }
        .admin-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin-top: 8px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-badge.pending {
            background: #ffc107;
            color: #000;
        }
        .status-badge.approved {
            background: #28a745;
            color: white;
        }
        .status-badge.rejected {
            background: #dc3545;
            color: white;
        }
        .status-badge.processing {
            background: #17a2b8;
            color: white;
        }
        .order-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .order-type-option {
            flex: 1;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .order-type-option:hover {
            border-color: #4a90e2;
        }
        .order-type-option.selected {
            border-color: #4a90e2;
            background: #f0f8ff;
        }
        .order-type-option input[type="radio"] {
            display: none;
        }
        .order-type-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .order-type-description {
            font-size: 0.9em;
            color: #666;
        }
        .form-section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #4a90e2;
        }
        .order-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
            margin-left: 8px;
        }
        .order-type-badge.prescription {
            background: #007bff;
            color: white;
        }
        .order-type-badge.online_order {
            background: #28a745;
            color: white;
        }
        .file-info-dynamic {
            margin-top: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9em;
            display: none;
        }
    </style>
</head>

<body>

    @include('client.client-header')

    <div class="main-container">

        <div class="panel">
            <h2>Upload Your Document</h2>

            @php $prescriptions = $prescriptions ?? collect(); @endphp

            @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
            <div class="success-message">
                <p><strong>Scan this QR code at the pharmacy:</strong></p>
                @if(session('qr_image'))
                <img src="{{ session('qr_image') }}" alt="QR Code for pre-order" style="max-width: 250px;">
                @endif
                <p><strong>Your order link:</strong></p>
                <p><a href="{{ session('qr_link') }}" target="_blank">{{ session('qr_link') }}</a></p>
            </div>
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

            <form action="{{ route('prescription.upload') }}" method="POST" enctype="multipart/form-data" class="upload-form">
                @csrf

                <div class="form-section">
                    <div class="section-title">Order Type</div>
                    <div class="order-type-selector">
                        <label class="order-type-option" for="prescription">
                            <input type="radio" id="prescription" name="order_type" value="prescription" {{ old('order_type', 'prescription') === 'prescription' ? 'checked' : '' }}>
                            <div class="order-type-title">Prescription Upload</div>
                            <div class="order-type-description">Upload a doctor's prescription for validation and processing</div>
                        </label>

                        <label class="order-type-option" for="online_order">
                            <input type="radio" id="online_order" name="order_type" value="online_order" {{ old('order_type') === 'online_order' ? 'checked' : '' }}>
                            <div class="order-type-title">Medicine List Order</div>
                            <div class="order-type-description">Upload a list of medicines you want to order directly</div>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Order Details</div>
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number</label>
                        <input type="text" id="mobile_number" name="mobile_number" required placeholder="e.g. 09123456789" value="{{ old('mobile_number') }}" />
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any additional information...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="prescription_file" id="file-label">Upload Document (JPG, PNG, PDF)</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="prescription_file" name="prescription_file" accept=".jpg,.jpeg,.png,.pdf" required />
                        </div>
                        <small id="file-security-note" style="color: #666; margin-top: 4px; display: block;">
                            Your document will be securely encrypted and can only be viewed by authorized pharmacy staff.
                        </small>
                        <div class="file-info-dynamic" id="file-info"></div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Submit Order</button>
            </form>
        </div>

        <div class="panel">
            <h3>Your Order History</h3>

            <div id="preorder-history">
                @forelse ($prescriptions as $prescription)
                <div class="history-card">
                    <div class="history-info">
                        <strong>Order ID:</strong> {{ $prescription->order->order_id ?? 'N/A' }}
                        <span class="order-type-badge {{ $prescription->order_type ?? 'prescription' }}">
                            {{ $prescription->order_type === 'online_order' ? 'Medicine List' : 'Prescription' }}
                        </span><br>
                        <strong>Status:</strong>
                        <span class="status-badge {{ strtolower($prescription->status ?? 'pending') }}">
                            {{ ucfirst($prescription->status ?? 'Pending') }}
                        </span><br>
                        <strong>Uploaded:</strong> {{ $prescription->created_at->format('M d, Y h:i A') }}<br>
                        <strong>Notes:</strong> {{ $prescription->notes ?? '—' }}<br>

                        @if($prescription->is_encrypted && $prescription->original_filename)
                        <div class="encrypted-file-info">
                            <strong>Your Document:</strong> {{ $prescription->original_filename }}
                            <span class="security-badge">ENCRYPTED</span>
                            @if($prescription->file_size)
                            <div class="file-size">Size: {{ number_format($prescription->file_size / 1024, 1) }} KB</div>
                            @endif
                            <div style="font-size: 0.8em; color: #666; margin-top: 4px;">
                                File securely encrypted. Only pharmacy staff can view this document.
                            </div>
                        </div>
                        @elseif($prescription->file_path)
                        <strong>Your Document:</strong>
                        <a href="{{ asset('storage/' . $prescription->file_path) }}" target="_blank">View Document</a><br>
                        @endif

                        @if ($prescription->qr_code_path)
                        <strong>QR Code:</strong>
                        <a href="{{ route('prescription.qr', $prescription->id) }}" target="_blank">View QR Code</a><br>
                        @endif

                        @if ($prescription->admin_message)
                        <div class="admin-message">
                            <strong>Message from Pharmacy:</strong><br>
                            {{ $prescription->admin_message }}
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <p class="no-history">No orders uploaded yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    <script>
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
                fileLabel.textContent = 'Upload Prescription (JPG, PNG, PDF)';
                securityNote.textContent = 'Your prescription will be securely encrypted and can only be viewed by authorized pharmacy staff.';
            } else if (onlineOrderRadio.checked) {
                onlineOrderRadio.closest('.order-type-option').classList.add('selected');
                fileLabel.textContent = 'Upload Medicine List (JPG, PNG, PDF)';
                securityNote.textContent = 'Your medicine list will be securely encrypted and processed by our pharmacy staff.';
            }
        }

        // Initialize UI on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateOrderTypeUI();

            // Add event listeners for order type changes
            document.querySelectorAll('input[name="order_type"]').forEach(radio => {
                radio.addEventListener('change', updateOrderTypeUI);
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
                    <strong>Selected:</strong> ${fileName}<br>
                    <strong>Size:</strong> ${fileSizeKB} KB<br>
                    <strong>Type:</strong> ${file.type}
                `;
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
        });

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
