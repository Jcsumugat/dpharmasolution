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
    </style>
</head>

<body>

    @include('client.client-header')

    <div class="main-container">

        <!-- Left Panel: Upload Form -->
        <div class="panel">
            <h2>📄 Upload Your Prescription</h2>

            @php $prescriptions = $prescriptions ?? collect(); @endphp

            @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
            <div class="success-message">
                <p><strong>🎉 Scan this QR code at the pharmacy:</strong></p>
                @if(session('qr_image'))
                <img src="{{ session('qr_image') }}" alt="QR Code for prescription pre-order" style="max-width: 250px;">
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
                <div class="form-group">
                    <label for="mobile_number">📱 Mobile Number</label>
                    <input type="text" id="mobile_number" name="mobile_number" required placeholder="e.g. 09123456789" value="{{ old('mobile_number') }}" />
                </div>

                <div class="form-group">
                    <label for="notes">📝 Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Any additional information about your prescription...">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="prescription_file">📷 Upload Prescription (JPG, PNG, PDF)</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="prescription_file" name="prescription_file" accept=".jpg,.jpeg,.png,.pdf" required />
                    </div>
                    <small style="color: #666; margin-top: 4px; display: block;">
                        🔒 Your prescription will be securely encrypted and can only be viewed by authorized pharmacy staff.
                    </small>
                </div>

                <button type="submit" class="btn-submit">✨ Submit Prescription</button>
            </form>
        </div>

        <!-- Right Panel: History -->
        <div class="panel">
            <h3>🕘 Your Pre-Order History</h3>

            <div id="preorder-history">
                @forelse ($prescriptions as $prescription)
                <div class="history-card">
                    <div class="history-info">
                        <strong>Order ID:</strong> {{ $prescription->order->order_id ?? 'N/A' }}<br>
                        <strong>Status:</strong>
                        <span class="status-badge {{ strtolower($prescription->status ?? 'pending') }}">
                            {{ ucfirst($prescription->status ?? 'Pending') }}
                        </span><br>
                        <strong>Uploaded:</strong> {{ $prescription->created_at->format('M d, Y h:i A') }}<br>
                        <strong>Notes:</strong> {{ $prescription->notes ?? '—' }}<br>
                        
                        <!-- Encrypted File Information -->
                        @if($prescription->is_encrypted && $prescription->original_filename)
                        <div class="encrypted-file-info">
                            <strong>📄 Your Prescription:</strong> {{ $prescription->original_filename }}
                            <span class="security-badge">🔒 ENCRYPTED</span>
                            @if($prescription->file_size)
                            <div class="file-size">Size: {{ number_format($prescription->file_size / 1024, 1) }} KB</div>
                            @endif
                            <div style="font-size: 0.8em; color: #666; margin-top: 4px;">
                                File securely encrypted. Only pharmacy staff can view this document.
                            </div>
                        </div>
                        @elseif($prescription->file_path)
                        <!-- Fallback for non-encrypted files (legacy) -->
                        <strong>Your Prescription:</strong> 
                        <a href="{{ asset('storage/' . $prescription->file_path) }}" target="_blank">View Document</a><br>
                        @endif

                        @if ($prescription->qr_code_path)
                        <strong>QR Code:</strong>
                        <a href="{{ route('prescription.qr', $prescription->id) }}" target="_blank">View QR Code</a><br>
                        @endif

                        @if ($prescription->admin_message)
                        <div class="admin-message">
                            <strong>📢 Message from Pharmacy:</strong><br>
                            {{ $prescription->admin_message }}
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <p class="no-history">No prescriptions uploaded yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    <script>
        // Add file validation on frontend
        document.getElementById('prescription_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = file.size;
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (fileSize > maxSize) {
                    alert('File size must be less than 5MB');
                    e.target.value = '';
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only JPG, PNG, or PDF files');
                    e.target.value = '';
                    return;
                }

                // Show file info
                const fileName = file.name;
                const fileSizeKB = (fileSize / 1024).toFixed(1);
                console.log(`Selected file: ${fileName} (${fileSizeKB} KB)`);
            }
        });

        // Auto-format mobile number input
        document.getElementById('mobile_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            e.target.value = value;
        });
    </script>

    @stack('scripts')
</body>

</html>