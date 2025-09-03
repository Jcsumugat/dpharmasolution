<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/uploads.css') }}">
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
                            <div class="order-type-title">Non Prescription Upload</div>
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

                        <!-- Chat Button Section -->
                        <div class="order-actions" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e0e0e0;">
                            <button class="chat-btn" data-prescription-id="{{ $prescription->id }}" data-order-id="{{ $prescription->order->order_id ?? 'N/A' }}">
                                💬 Chat with Pharmacy
                                @if($prescription->unreadMessagesForCustomer()->count() > 0)
                                    <span class="unread-badge">{{ $prescription->unreadMessagesForCustomer()->count() }}</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <p class="no-history">No orders uploaded yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Customer Chat Modal -->
    <div id="customerChatModal" class="customer-chat-modal">
        <div class="customer-chat-container">
            <div class="customer-chat-header">
                <div class="customer-chat-title">
                    <span id="customerChatOrderId">Chat with Pharmacy</span>
                </div>
                <button class="customer-chat-close" id="closeCustomerChatModal">&times;</button>
            </div>
            <div class="customer-chat-messages" id="customerChatMessages">
                <div class="customer-no-messages">No messages yet. Start the conversation!</div>
            </div>
            <div class="customer-chat-input-container">
                <textarea class="customer-chat-input" id="customerChatInput" placeholder="Type your message..." rows="1"></textarea>
                <button class="customer-chat-send" id="customerSendMessage">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/>
                    </svg>
                </button>
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

        // Customer Chat Functionality
        let currentCustomerChatPrescriptionId = null;

        // Customer chat elements
        const customerChatModal = document.getElementById('customerChatModal');
        const customerChatMessages = document.getElementById('customerChatMessages');
        const customerChatInput = document.getElementById('customerChatInput');
        const customerSendButton = document.getElementById('customerSendMessage');
        const customerChatOrderId = document.getElementById('customerChatOrderId');

        // CSRF token function
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        }

        // Open customer chat modal
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('chat-btn')) {
                const prescriptionId = e.target.dataset.prescriptionId;
                const orderId = e.target.dataset.orderId;

                currentCustomerChatPrescriptionId = prescriptionId;

                if (customerChatOrderId) {
                    customerChatOrderId.textContent = `Chat - Order ${orderId}`;
                }

                if (customerChatModal) {
                    customerChatModal.classList.add('active');
                    loadCustomerMessages(prescriptionId);
                    markCustomerMessagesAsRead(prescriptionId);
                }
            }
        });

        // Close customer chat modal
        document.getElementById('closeCustomerChatModal')?.addEventListener('click', () => {
            if (customerChatModal) {
                customerChatModal.classList.remove('active');
            }
            currentCustomerChatPrescriptionId = null;
        });

        // Close customer chat when clicking outside
        customerChatModal?.addEventListener('click', (e) => {
            if (e.target === customerChatModal) {
                customerChatModal.classList.remove('active');
                currentCustomerChatPrescriptionId = null;
            }
        });

        // Load customer messages
        async function loadCustomerMessages(prescriptionId) {
            if (!customerChatMessages) return;

            customerChatMessages.innerHTML = '<div class="customer-no-messages">Loading messages...</div>';

            try {
                const response = await fetch(`/my-orders/${prescriptionId}/messages`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCSRFToken()
                    }
                });

                const data = await response.json();

                if (data.success) {
                    displayCustomerMessages(data.messages);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                customerChatMessages.innerHTML = '<div class="customer-no-messages">Error loading messages</div>';
            }
        }

        // Display customer messages
        function displayCustomerMessages(messages) {
            if (!customerChatMessages) return;

            if (messages.length === 0) {
                customerChatMessages.innerHTML = '<div class="customer-no-messages">No messages yet. Start the conversation!</div>';
                return;
            }

            customerChatMessages.innerHTML = '';

            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `customer-message ${message.sender_type}`;

                messageDiv.innerHTML = `
                    <div class="customer-message-avatar">${message.sender_type === 'admin' ? 'P' : 'C'}</div>
                    <div class="customer-message-content">
                        <div class="customer-message-bubble">${message.message}</div>
                        <div class="customer-message-time">${message.created_at}</div>
                    </div>
                `;

                customerChatMessages.appendChild(messageDiv);
            });

            // Scroll to bottom
            customerChatMessages.scrollTop = customerChatMessages.scrollHeight;
        }

        // Send customer message
        async function sendCustomerMessage() {
            if (!currentCustomerChatPrescriptionId || !customerChatInput) return;

            const message = customerChatInput.value.trim();
            if (!message) return;

            const originalText = customerSendButton ? customerSendButton.innerHTML : '';
            if (customerSendButton) {
                customerSendButton.disabled = true;
                customerSendButton.innerHTML = '...';
            }

            try {
                const response = await fetch(`/my-orders/${currentCustomerChatPrescriptionId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCSRFToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();

                if (data.success) {
                    customerChatInput.value = '';

                    // Add message to display
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'customer-message customer';
                    messageDiv.innerHTML = `
                        <div class="customer-message-avatar">C</div>
                        <div class="customer-message-content">
                            <div class="customer-message-bubble">${data.message.message}</div>
                            <div class="customer-message-time">${data.message.created_at}</div>
                        </div>
                    `;

                    if (customerChatMessages.querySelector('.customer-no-messages')) {
                        customerChatMessages.innerHTML = '';
                    }

                    customerChatMessages.appendChild(messageDiv);
                    customerChatMessages.scrollTop = customerChatMessages.scrollHeight;
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            } finally {
                if (customerSendButton) {
                    customerSendButton.disabled = false;
                    customerSendButton.innerHTML = originalText;
                }
            }
        }

        // Customer send message events
        customerSendButton?.addEventListener('click', sendCustomerMessage);

        customerChatInput?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendCustomerMessage();
            }
        });

        // Auto-resize customer textarea
        customerChatInput?.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Mark customer messages as read
        async function markCustomerMessagesAsRead(prescriptionId) {
            try {
                await fetch(`/my-orders/${prescriptionId}/messages/mark-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCSRFToken(),
                        'Accept': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }
    </script>

    @stack('scripts')
</body>

</html>
