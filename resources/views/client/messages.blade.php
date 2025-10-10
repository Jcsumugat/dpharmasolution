<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/customer/messages.css') }}" />
    <title>Message the Store - MJ's Pharmacy</title>
</head>

<body>
    @include('client.client-header')

    <div class="main-content">
        <div class="chat-container">
            <div class="chat-header">
                <div class="header-info">
                    <h2>Message the Store</h2>
                    <p>Contact us directly for any inquiries</p>
                </div>
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span>Online</span>
                </div>
            </div>

            <div id="errorContainer"></div>
            <div id="successContainer"></div>

            <div class="chat-messages" id="chatMessages">
                <div class="loading-messages">
                    <div class="loading-spinner"></div>
                    <div>Connecting...</div>
                </div>
            </div>

            <div class="typing-indicator" id="typingIndicator">
                Store representative is typing...
            </div>

            <div class="chat-input">
                <div class="attachment-preview" id="attachmentPreview"
                    style="display: none; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e9ecef;">
                    <div class="attachment-item">
                        <span>No files selected</span>
                    </div>
                </div>

                <div class="input-container">
                    <button class="attachment-btn" onclick="document.getElementById('fileInput').click()"
                        title="Attach File">
                        📄
                    </button>
                    <textarea class="message-input" id="messageInput" placeholder="Type your message..." rows="1"></textarea>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()" disabled>
                        ➤
                    </button>
                </div>

                <input type="file" id="fileInput" multiple accept="image/*,application/pdf,.doc,.docx"
                    style="display: none;">
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let currentCustomerId = {{ Auth::guard('customer')->user()->customer_id ?? 'null' }};
        let pollingInterval = null;
        let selectedFiles = [];
        let isTyping = false;

        document.addEventListener('DOMContentLoaded', function() {
            if (!currentCustomerId) {
                showError('Please log in to access chat support');
                return;
            }

            initializeChat();
            setupInput();
            setupFileUpload();
        });

        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        });

        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Fix viewport height on mobile browsers
        function setViewportHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }

        window.addEventListener('resize', setViewportHeight);
        window.addEventListener('orientationchange', setViewportHeight);
        setViewportHeight();

        async function initializeChat() {
            try {
                await findOrCreateConversation();
                if (currentConversationId) {
                    await loadMessages();
                    startPolling();
                }
            } catch (error) {
                console.error('Failed to initialize chat:', error);
                showError('Failed to connect to support. Please refresh the page.');
            }
        }

        async function findOrCreateConversation() {
            try {
                const response = await fetch('/api/customer/chat/conversations/find-or-create', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_id: currentCustomerId,
                        type: 'general_support'
                    })
                });

                if (!response.ok) throw new Error('Failed to create conversation');

                const data = await response.json();
                currentConversationId = data.conversation.id;

            } catch (error) {
                console.error('Error creating conversation:', error);
                throw error;
            }
        }

        async function loadMessages(silent = false) {
            if (!currentConversationId) return;

            if (!silent) {
                document.getElementById('chatMessages').innerHTML = `
                    <div class="loading-messages">
                        <div class="loading-spinner"></div>
                        <div>Loading messages...</div>
                    </div>
                `;
            }

            try {
                const response = await fetch(`/api/customer/chat/conversations/${currentConversationId}/messages`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to load messages');

                const data = await response.json();
                displayMessages(data.messages);

            } catch (error) {
                console.error('Error loading messages:', error);
                if (!silent) {
                    document.getElementById('chatMessages').innerHTML = `
                        <div class="error-message">Failed to load messages</div>
                    `;
                }
            }
        }

        function displayMessages(messages) {
            const container = document.getElementById('chatMessages');
            let html = '';

            if (messages.length === 0) {
                html = '<div class="no-messages">No messages yet. Start the conversation!</div>';
            } else {
                messages.forEach(message => {
                    const messageClass = message.is_from_customer ? 'customer' : 'admin';
                    const senderName = message.is_from_customer ? 'You' : 'Store';

                    html += `
                <div class="message ${messageClass}">
                    <div class="message-bubble">
                        ${message.message ? `<div>${message.message}</div>` : ''}
                        ${message.has_attachments ? renderAttachments(message.attachments) : ''}
                        <div class="message-time">${message.time_ago} • ${senderName}</div>
                    </div>
                </div>
            `;
                });
            }

            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }

        function renderAttachments(attachments) {
            let html = '<div class="message-attachments">';

            attachments.forEach(attachment => {
                const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(attachment.file_type.toLowerCase());

                if (isImage) {
                    html += `
                <div class="attachment-item image-attachment">
                    <img src="/storage/${attachment.file_path}" alt="${attachment.file_name}"
                         style="max-width: 200px; max-height: 200px; border-radius: 8px; margin: 5px 0;"
                         onclick="window.open('/storage/${attachment.file_path}', '_blank')">
                    <div class="attachment-name">${attachment.file_name}</div>
                </div>
            `;
                } else {
                    html += `
                <div class="attachment-item file-attachment">
                    <div class="file-icon">📄</div>
                    <div class="file-info">
                        <div class="file-name">${attachment.file_name}</div>
                        <div class="file-size">${formatFileSize(attachment.file_size)}</div>
                    </div>
                    <a href="/api/customer/chat/download/${attachment.id}"
                       class="download-btn" download="${attachment.file_name}">⬇️</a>
                </div>
            `;
                }
            });

            html += '</div>';
            return html;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message && selectedFiles.length === 0) return;
            if (!currentConversationId) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML =
                '<div class="loading-spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>';

            try {
                const formData = new FormData();
                formData.append('message', message || '');
                formData.append('message_type', selectedFiles.length > 0 ? 'file' : 'text');

                selectedFiles.forEach(file => {
                    formData.append('attachments[]', file);
                });

                const response = await fetch(`/api/customer/chat/conversations/${currentConversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Failed to send message');

                messageInput.value = '';
                selectedFiles = [];
                document.getElementById('attachmentPreview').style.display = 'none';
                document.getElementById('fileInput').value = '';

                await loadMessages();
                showSuccess('Message sent successfully');

            } catch (error) {
                console.error('Error sending message:', error);
                showError('Failed to send message. Please try again.');
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '➤';
            }
        }

        function setupInput() {
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');

            function updateSendButtonState() {
                sendBtn.disabled = !messageInput.value.trim() && selectedFiles.length === 0;
            }

            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';

                updateSendButtonState();

                if (this.value.trim() && !isTyping) {
                    updateTypingStatus(true);
                } else if (!this.value.trim() && isTyping) {
                    updateTypingStatus(false);
                }
            });

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!sendBtn.disabled) {
                        sendMessage();
                    }
                }
            });

            messageInput.addEventListener('blur', function() {
                if (isTyping) {
                    updateTypingStatus(false);
                }
            });
        }

        function setupFileUpload() {
            document.getElementById('fileInput').addEventListener('change', function(e) {
                selectedFiles = Array.from(e.target.files);
                displayAttachments();

                const messageInput = document.getElementById('messageInput');
                const sendBtn = document.getElementById('sendBtn');
                sendBtn.disabled = !messageInput.value.trim() && selectedFiles.length === 0;
            });
        }

        function displayAttachments() {
            const preview = document.getElementById('attachmentPreview');

            if (selectedFiles.length === 0) {
                preview.style.display = 'none';
                return;
            }

            let html = '';
            selectedFiles.forEach((file, index) => {
                const isImage = file.type.startsWith('image/');
                const fileSize = formatFileSize(file.size);

                html += `
                    <div class="attachment-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #fff; border-radius: 6px; margin-bottom: 5px; border: 1px solid #ddd;">
                        <div class="file-icon" style="font-size: 18px; width: 24px; text-align: center;">
                            ${isImage ? '🖼️' : '📄'}
                        </div>
                        <div class="file-info" style="flex: 1; min-width: 0;">
                            <div class="file-name" style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px;">
                                ${file.name}
                            </div>
                            <div class="file-size" style="font-size: 12px; color: #666;">
                                ${fileSize}
                            </div>
                        </div>
                        <button onclick="removeAttachment(${index})" class="remove-attachment"
                                style="background: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; line-height: 1;">
                            ×
                        </button>
                    </div>
                `;
            });

            preview.innerHTML = html;
            preview.style.display = 'block';
        }

        function removeAttachment(index) {
            selectedFiles.splice(index, 1);
            displayAttachments();

            const sendBtn = document.getElementById('sendBtn');
            const messageInput = document.getElementById('messageInput');
            sendBtn.disabled = !messageInput.value.trim() && selectedFiles.length === 0;
        }

        async function updateTypingStatus(typing) {
            if (!currentConversationId) return;

            isTyping = typing;

            try {
                await fetch('/api/customer/chat/typing-status', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_id: currentCustomerId,
                        conversation_id: currentConversationId,
                        is_typing: typing
                    })
                });
            } catch (error) {
                console.error('Error updating typing status:', error);
            }
        }

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);

            pollingInterval = setInterval(() => {
                if (currentConversationId) {
                    loadMessages(true);
                }
            }, 10000);
        }

        function showError(message) {
            const container = document.getElementById('errorContainer');
            container.innerHTML = `<div class="error-message">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }

        function showSuccess(message) {
            const container = document.getElementById('successContainer');
            container.innerHTML = `<div class="success-message">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }

        window.addEventListener('beforeunload', () => {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            if (isTyping) {
                updateTypingStatus(false);
            }
        });

        window.addEventListener('focus', () => {
            if (currentConversationId && !pollingInterval) {
                startPolling();
            }
        });

        window.addEventListener('blur', () => {
            if (isTyping) {
                updateTypingStatus(false);
            }
        });

        function handleMobileViewport() {
            const setHeight = () => {
                const vh = window.visualViewport ? window.visualViewport.height : window.innerHeight;
                document.documentElement.style.setProperty('--vh', `${vh * 0.01}px`);
            };

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', setHeight);
            }

            window.addEventListener('resize', setHeight);
            setHeight();
        }

        // Prevent input from being hidden by keyboard
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('focus', function() {
                setTimeout(() => {
                    this.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 300);
            });
        }

        handleMobileViewport();
    </script>

    @stack('scripts')
</body>
@include('client.client-footer')
</html>
