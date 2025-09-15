<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Chat - MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
</head>

<body>
    @include('admin.admin-header')

    @if (session('success'))
        <div class="alert alert-success" id="flashMessage">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container fade-in">
        <div class="header-bar">
            <h2 class="page-title">Customer Chat</h2>
            <div class="header-actions">
                <div class="stats-summary" id="statsContainer">
                    <div class="stat-item">
                        <span class="stat-number" id="onlineCount">0</span>
                        <span class="stat-label">Online</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="totalCount">0</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="activeChats">0</span>
                        <span class="stat-label">Active Chats</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="customers-container">
            <div class="customers-header">
                <div class="search-controls">
                    <input
                        type="text"
                        class="search-input"
                        id="customerSearch"
                        placeholder="Search customers..."
                    >
                    <select class="status-filter" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="on">Online</option>
                        <option value="off">Offline</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Last Active</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <tr>
                            <td colspan="5" class="loading-table">
                                <div class="loading-spinner"></div>
                                <div>Loading customers...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="chat-modal" id="chatModal">
        <div class="chat-modal-content">
            <div class="chat-modal-header">
                <div class="customer-info">
                    <div class="customer-avatar" id="modalCustomerAvatar"></div>
                    <div>
                        <div id="modalCustomerName"></div>
                        <div class="status-indicator" id="modalCustomerStatus">
                            <span class="status-dot"></span>
                            <span></span>
                        </div>
                    </div>
                </div>
                <button class="chat-modal-close" onclick="closeChatModal()">&times;</button>
            </div>

            <div class="chat-modal-messages" id="modalMessages">
                <div class="loading-messages">
                    <div class="loading-spinner"></div>
                    <div>Loading messages...</div>
                </div>
            </div>

            <div class="typing-indicator" id="typingIndicator">
                Customer is typing...
            </div>

            <div class="chat-modal-input">
                <div class="attachment-preview" id="modalAttachmentPreview">
                    <div class="attachment-item">
                        <span>No files selected</span>
                    </div>
                </div>

                <div class="modal-input-container">
                    <button class="modal-attachment-btn" onclick="document.getElementById('modalFileInput').click()" title="Attach File">
                        📎
                    </button>
                    <textarea
                        class="modal-message-input"
                        id="modalMessageInput"
                        placeholder="Type a message..."
                        rows="1"
                    ></textarea>
                    <button class="modal-send-btn" id="modalSendBtn" onclick="sendModalMessage()" disabled>
                        ➤
                    </button>
                </div>

                <input type="file" id="modalFileInput" multiple accept="image/*,application/pdf,.doc,.docx" style="display: none;">
            </div>
        </div>
    </div>

    <script>
        let customers = [];
        let currentChatCustomer = null;
        let currentConversationId = null;
        let modalPollingInterval = null;
        let selectedModalFiles = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadCustomers();
            loadStats();
            setupModalInput();
            setupModalFileUpload();
            setupSearch();

            setInterval(() => {
                loadCustomers(true);
                loadStats();
                if (currentConversationId) {
                    loadModalMessages(currentConversationId, true);
                }
            }, 30000);

            const flashMessage = document.getElementById('flashMessage');
            if (flashMessage) {
                setTimeout(() => flashMessage.style.display = 'none', 5000);
            }
        });

        async function loadCustomers(silent = false) {
            if (!silent) {
                document.getElementById('customersTableBody').innerHTML = `
                    <tr>
                        <td colspan="5" class="loading-table">
                            <div class="loading-spinner"></div>
                            <div>Loading customers...</div>
                        </td>
                    </tr>
                `;
            }

            try {
                const response = await fetch('/api/admin/customers/chat', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || `HTTP ${response.status}`);
                }

                customers = data.customers || [];
                displayCustomers(customers);

            } catch (error) {
                console.error('Error loading customers:', error);
                if (!silent) {
                    document.getElementById('customersTableBody').innerHTML = `
                        <tr>
                            <td colspan="5" class="error-state">
                                <div>Failed to load customers</div>
                                <div style="font-size: 0.8em; color: #666; margin-top: 5px;">
                                    Error: ${error.message}
                                </div>
                                <button onclick="loadCustomers()" class="btn btn-sm" style="margin-top: 10px;">
                                    Retry
                                </button>
                            </td>
                        </tr>
                    `;
                }
            }
        }

        function displayCustomers(customerList) {
            const tbody = document.getElementById('customersTableBody');

            if (!customerList || customerList.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="no-customers">
                            <div>No customers found</div>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            customerList.forEach(customer => {
                const initials = getInitials(customer.full_name || 'Unknown');
                const statusClass = `status-${customer.chat_status || 'off'}`;

                html += `
                    <tr>
                        <td>
                            <div class="customer-info">
                                <div class="customer-avatar">${initials}</div>
                                <div>
                                    <div class="customer-name">${customer.full_name || 'Unknown'}</div>
                                    <div class="customer-id">ID: ${customer.customer_id || 'N/A'}</div>
                                </div>
                            </div>
                        </td>
                        <td>${customer.email_address || 'No email'}</td>
                        <td>
                            <div class="status-indicator">
                                <span class="status-dot ${statusClass}"></span>
                                <span>${formatStatus(customer.chat_status || 'off')}</span>
                            </div>
                        </td>
                        <td>
                            <div class="last-active">${formatLastActive(customer.last_active)}</div>
                        </td>
                        <td>
                            <button class="chat-button" onclick="openChatModal('${customer.customer_id}')">
                                Chat
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        async function openChatModal(customerId) {
            const customer = customers.find(c => c.customer_id == customerId);
            if (!customer) return;

            currentChatCustomer = customer;

            document.getElementById('modalCustomerAvatar').textContent = getInitials(customer.full_name);
            document.getElementById('modalCustomerName').textContent = customer.full_name;

            const statusElement = document.getElementById('modalCustomerStatus');
            statusElement.innerHTML = `
                <span class="status-dot status-${customer.chat_status || 'off'}"></span>
                <span>${formatStatus(customer.chat_status || 'off')}</span>
            `;

            document.getElementById('chatModal').classList.add('show');
            document.body.style.overflow = 'hidden';

            await findOrCreateConversation(customerId);

            if (currentConversationId) {
                await loadModalMessages(currentConversationId);
                startModalPolling();
            }
        }

        function closeChatModal() {
            document.getElementById('chatModal').classList.remove('show');
            document.body.style.overflow = '';

            currentChatCustomer = null;
            currentConversationId = null;
            selectedModalFiles = [];

            document.getElementById('modalMessageInput').value = '';
            document.getElementById('modalAttachmentPreview').style.display = 'none';

            if (modalPollingInterval) {
                clearInterval(modalPollingInterval);
                modalPollingInterval = null;
            }
        }

        async function findOrCreateConversation(customerId) {
            try {
                const response = await fetch('/api/admin/chat/conversations/find-or-create', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_id: customerId,
                        type: 'general_support'
                    })
                });

                if (!response.ok) throw new Error('Failed to find/create conversation');

                const data = await response.json();
                currentConversationId = data.conversation.id;
            } catch (error) {
                console.error('Error finding/creating conversation:', error);
                alert('Failed to start conversation');
            }
        }

        async function loadModalMessages(conversationId, silent = false) {
            if (!silent) {
                document.getElementById('modalMessages').innerHTML = `
                    <div class="loading-messages">
                        <div class="loading-spinner"></div>
                        <div>Loading messages...</div>
                    </div>
                `;
            }

            try {
                const response = await fetch(`/api/admin/chat/conversations/${conversationId}/messages`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to load messages');

                const data = await response.json();
                displayModalMessages(data.messages);
            } catch (error) {
                console.error('Error loading messages:', error);
                if (!silent) {
                    document.getElementById('modalMessages').innerHTML = `
                        <div class="error-state">Failed to load messages</div>
                    `;
                }
            }
        }

        function displayModalMessages(messages) {
            const container = document.getElementById('modalMessages');
            let html = '';

            if (messages.length === 0) {
                html = '<div class="no-messages">No messages yet. Start the conversation!</div>';
            } else {
                messages.forEach(message => {
                    const messageClass = message.is_from_customer ? 'customer' : 'admin';

                    html += `
                        <div class="modal-message ${messageClass}">
                            <div class="modal-message-content">
                                ${message.message}
                                ${message.has_attachments ? '<div class="message-attachments">📎 Attachments</div>' : ''}
                            </div>
                            <div class="modal-message-time">${message.time_ago}</div>
                        </div>
                    `;
                });
            }

            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }

        async function sendModalMessage() {
            const messageInput = document.getElementById('modalMessageInput');
            const message = messageInput.value.trim();

            if (!message && selectedModalFiles.length === 0) return;
            if (!currentConversationId) return;

            const sendBtn = document.getElementById('modalSendBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<div class="loading-spinner small"></div>';

            try {
                const formData = new FormData();
                formData.append('message', message);
                formData.append('message_type', selectedModalFiles.length > 0 ? 'file' : 'text');
                formData.append('is_internal_note', false);

                selectedModalFiles.forEach(file => {
                    formData.append('attachments[]', file);
                });

                const response = await fetch(`/api/admin/chat/conversations/${currentConversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Failed to send message');

                messageInput.value = '';
                selectedModalFiles = [];
                document.getElementById('modalAttachmentPreview').style.display = 'none';

                await loadModalMessages(currentConversationId);

            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '➤';
            }
        }

        function setupModalInput() {
            const messageInput = document.getElementById('modalMessageInput');
            const sendBtn = document.getElementById('modalSendBtn');

            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';

                sendBtn.disabled = !this.value.trim() && selectedModalFiles.length === 0;
            });

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!sendBtn.disabled) {
                        sendModalMessage();
                    }
                }
            });
        }

        function setupModalFileUpload() {
            document.getElementById('modalFileInput').addEventListener('change', function(e) {
                selectedModalFiles = Array.from(e.target.files);
                displayModalAttachments();
                document.getElementById('modalSendBtn').disabled = false;
            });
        }

        function displayModalAttachments() {
            const preview = document.getElementById('modalAttachmentPreview');

            if (selectedModalFiles.length === 0) {
                preview.style.display = 'none';
                return;
            }

            let html = '';
            selectedModalFiles.forEach((file, index) => {
                html += `
                    <div class="attachment-item">
                        <span>${file.name}</span>
                        <button onclick="removeModalAttachment(${index})" class="remove-attachment">✕</button>
                    </div>
                `;
            });

            preview.innerHTML = html;
            preview.style.display = 'block';
        }

        function removeModalAttachment(index) {
            selectedModalFiles.splice(index, 1);
            displayModalAttachments();

            const sendBtn = document.getElementById('modalSendBtn');
            const messageInput = document.getElementById('modalMessageInput');
            sendBtn.disabled = !messageInput.value.trim() && selectedModalFiles.length === 0;
        }

        function startModalPolling() {
            if (modalPollingInterval) clearInterval(modalPollingInterval);

            modalPollingInterval = setInterval(() => {
                if (currentConversationId) {
                    loadModalMessages(currentConversationId, true);
                }
            }, 3000);
        }

        function setupSearch() {
            const searchInput = document.getElementById('customerSearch');
            const statusFilter = document.getElementById('statusFilter');

            searchInput.addEventListener('input', filterCustomers);
            statusFilter.addEventListener('change', filterCustomers);
        }

        function filterCustomers() {
            const searchTerm = document.getElementById('customerSearch').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;

            let filteredCustomers = customers.filter(customer => {
                const matchesSearch =
                    (customer.full_name || '').toLowerCase().includes(searchTerm) ||
                    (customer.email_address || '').toLowerCase().includes(searchTerm) ||
                    (customer.customer_id || '').toString().includes(searchTerm);

                const matchesStatus = !statusFilter || (customer.chat_status || 'off') === statusFilter;

                return matchesSearch && matchesStatus;
            });

            displayCustomers(filteredCustomers);
        }

        async function loadStats() {
            try {
                const response = await fetch('/api/admin/customers/chat/stats', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('onlineCount').textContent = data.stats.online || 0;
                    document.getElementById('totalCount').textContent = data.stats.total || 0;
                    document.getElementById('activeChats').textContent = data.stats.active_chats || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        function getInitials(name) {
            if (!name) return 'UN';
            return name
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase()
                .substring(0, 2);
        }

        function formatStatus(status) {
            if (status === 'on') return 'Online';
            if (status === 'off') return 'Offline';
            return (status || 'offline').charAt(0).toUpperCase() + (status || 'offline').slice(1);
        }

        function formatLastActive(dateString) {
            if (!dateString) return 'Never';

            const date = new Date(dateString);
            const now = new Date();
            const diffInMinutes = Math.floor((now - date) / (1000 * 60));

            if (diffInMinutes < 1) return 'Just now';
            if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
            if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
            if (diffInMinutes < 10080) return `${Math.floor(diffInMinutes / 1440)}d ago`;

            return date.toLocaleDateString();
        }

        document.getElementById('chatModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeChatModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('chatModal').classList.contains('show')) {
                closeChatModal();
            }
        });

        window.addEventListener('beforeunload', () => {
            if (modalPollingInterval) {
                clearInterval(modalPollingInterval);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
