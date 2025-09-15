<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Message the Store - MJ's Pharmacy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .chat-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px;
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin: 0 auto;
        }

        .chat-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-info h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header-info p {
            opacity: 0.9;
            font-size: 14px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2ecc71;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }

        .message.customer {
            justify-content: flex-end;
        }

        .message.admin {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.customer .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message.admin .message-bubble {
            background: #e9ecef;
            color: #2c3e50;
        }

        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 5px;
        }

        .message.customer .message-time {
            text-align: right;
        }

        .typing-indicator {
            display: none;
            padding: 10px 20px;
            font-style: italic;
            color: #6c757d;
        }

        .typing-indicator.show {
            display: block;
        }

        .chat-input {
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .input-container {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 14px;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            outline: none;
            transition: border-color 0.3s;
        }

        .message-input:focus {
            border-color: #667eea;
        }

        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .send-btn:hover:not(:disabled) {
            transform: scale(1.1);
        }

        .send-btn:disabled {
            background: #ced4da;
            cursor: not-allowed;
            transform: none;
        }

        .attachment-btn {
            background: #6c757d;
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .attachment-btn:hover {
            background: #5a6268;
        }

        .attachment-preview {
            display: none;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .attachment-preview.show {
            display: block;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 0;
        }

        .attachment-item span {
            font-size: 14px;
            color: #495057;
        }

        .remove-attachment {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
        }

        .no-messages {
            text-align: center;
            color: #6c757d;
            padding: 40px;
            font-style: italic;
        }

        .loading-messages {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #e9ecef;
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 10px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 20px;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
        }

        .message-attachments {
            margin-top: 8px;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .message.admin .attachment-item {
            background: rgba(0, 0, 0, 0.05);
        }

        .image-attachment {
            flex-direction: column;
            align-items: flex-start;
        }

        .image-attachment img {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .image-attachment img:hover {
            transform: scale(1.05);
        }

        .attachment-name {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 4px;
        }

        .file-attachment {
            justify-content: space-between;
        }

        .file-icon {
            font-size: 20px;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-size: 13px;
            font-weight: 500;
        }

        .file-size {
            font-size: 11px;
            opacity: 0.7;
        }

        .download-btn {
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.2);
            transition: background-color 0.3s;
        }

        .download-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            .chat-container {
                height: calc(100vh - 120px);
            }

            .message-bubble {
                max-width: 85%;
            }

        }
    </style>
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
                <div class="attachment-preview" id="attachmentPreview">
                    <div class="attachment-item">
                        <span>No files selected</span>
                    </div>
                </div>

                <div class="input-container">
                    <button class="attachment-btn" onclick="document.getElementById('fileInput').click()"
                        title="Attach File">
                        📎
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
                formData.append('message', message);
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
                document.getElementById('attachmentPreview').classList.remove('show');

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

            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';

                sendBtn.disabled = !this.value.trim() && selectedFiles.length === 0;

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
                document.getElementById('sendBtn').disabled = false;
            });
        }

        function displayAttachments() {
            const preview = document.getElementById('attachmentPreview');

            if (selectedFiles.length === 0) {
                preview.classList.remove('show');
                return;
            }

            let html = '';
            selectedFiles.forEach((file, index) => {
                html += `
                    <div class="attachment-item">
                        <span>${file.name}</span>
                        <button onclick="removeAttachment(${index})" class="remove-attachment">✕</button>
                    </div>
                `;
            });

            preview.innerHTML = html;
            preview.classList.add('show');
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
            }, 3000);
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
    </script>

    @stack('scripts')
</body>

</html>
