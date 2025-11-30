@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
<style>
    .notifications-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 24px;
    }

    .notifications-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e5e7eb;
    }

    .notifications-page-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: #111827;
    }

    .notifications-actions {
        display: flex;
        gap: 12px;
    }

    .notifications-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-button {
        padding: 8px 16px;
        border: 2px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.2s;
    }

    .filter-button:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .filter-button.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .notifications-list-full {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .notification-item-full {
        display: flex;
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
    }

    .notification-item-full:hover {
        background: #f9fafb;
    }

    .notification-item-full.unread {
        background: #eff6ff;
    }

    .notification-item-full.unread:hover {
        background: #dbeafe;
    }

    .notification-checkbox {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .notification-icon-large {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 16px;
        flex-shrink: 0;
    }

    .notification-body {
        flex: 1;
        min-width: 0;
    }

    .notification-header-row {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 6px;
    }

    .notification-title-full {
        font-weight: 600;
        font-size: 1rem;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .notification-item-full.unread .notification-title-full {
        color: #1d4ed8;
    }

    .unread-dot {
        width: 8px;
        height: 8px;
        background: #3b82f6;
        border-radius: 50%;
    }

    .notification-time-full {
        font-size: 0.875rem;
        color: #9ca3af;
        flex-shrink: 0;
    }

    .notification-message-full {
        font-size: 0.9375rem;
        color: #6b7280;
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .notification-actions-row {
        display: flex;
        gap: 12px;
        margin-top: 8px;
    }

    .notification-action-btn {
        padding: 4px 12px;
        font-size: 0.8125rem;
        border: none;
        background: transparent;
        color: #3b82f6;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        border-radius: 4px;
    }

    .notification-action-btn:hover {
        background: #eff6ff;
    }

    .notification-action-btn.delete {
        color: #ef4444;
    }

    .notification-action-btn.delete:hover {
        background: #fef2f2;
    }

    .bulk-actions-bar {
        display: none;
        background: #3b82f6;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        align-items: center;
        justify-content: space-between;
    }

    .bulk-actions-bar.active {
        display: flex;
    }

    .bulk-actions-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .bulk-actions-buttons {
        display: flex;
        gap: 8px;
    }

    .bulk-action-btn {
        padding: 6px 16px;
        border: 2px solid white;
        background: transparent;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .bulk-action-btn:hover {
        background: white;
        color: #3b82f6;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }

    .empty-state-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 8px;
    }

    .empty-state-message {
        font-size: 0.9375rem;
    }

    .action-button {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    .action-button.primary {
        background: #3b82f6;
        color: white;
    }

    .action-button.primary:hover {
        background: #2563eb;
    }

    .action-button.secondary {
        background: white;
        color: #6b7280;
        border: 2px solid #e5e7eb;
    }

    .action-button.secondary:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .action-button.danger {
        background: #ef4444;
        color: white;
    }

    .action-button.danger:hover {
        background: #dc2626;
    }

    .loading-spinner {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }

    .spinner {
        border: 3px solid #f3f4f6;
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-size: 0.9375rem;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .toast.success {
        background: #10b981;
    }

    .toast.error {
        background: #ef4444;
    }

    .toast.show {
        transform: translateX(0);
    }
</style>

<div class="notifications-page">
    <div class="notifications-page-header">
        <h1 class="notifications-page-title">All Notifications</h1>
        <div class="notifications-actions">
            <button class="action-button primary" onclick="markAllAsRead()">
                Mark All as Read
            </button>
            <button class="action-button danger" onclick="clearAllRead()">
                Clear Read
            </button>
        </div>
    </div>

    <div class="notifications-filters">
        <button class="filter-button active" data-filter="all" onclick="filterNotifications('all')">
            All
        </button>
        <button class="filter-button" data-filter="unread" onclick="filterNotifications('unread')">
            Unread
        </button>
        <button class="filter-button" data-filter="stock" onclick="filterNotifications('stock')">
            📦 Stock Alerts
        </button>
        <button class="filter-button" data-filter="orders" onclick="filterNotifications('orders')">
            🛒 Orders
        </button>
        <button class="filter-button" data-filter="sales" onclick="filterNotifications('sales')">
            💰 Sales
        </button>
        <button class="filter-button" data-filter="system" onclick="filterNotifications('system')">
            ⚙️ System
        </button>
    </div>

    <div class="bulk-actions-bar" id="bulkActionsBar">
        <div class="bulk-actions-left">
            <span id="selectedCount">0 selected</span>
        </div>
        <div class="bulk-actions-buttons">
            <button class="bulk-action-btn" onclick="bulkMarkAsRead()">Mark as Read</button>
            <button class="bulk-action-btn" onclick="bulkDelete()">Delete</button>
            <button class="bulk-action-btn" onclick="deselectAll()">Deselect All</button>
        </div>
    </div>

    <div class="notifications-list-full" id="notificationsListFull">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div>Loading notifications...</div>
        </div>
    </div>
</div>

<script>
    let allNotifications = [];
    let currentFilter = 'all';
    let selectedNotifications = new Set();

    // Load notifications on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAllNotifications();
    });

    async function loadAllNotifications() {
        const container = document.getElementById('notificationsListFull');

        try {
            const response = await fetch('/notifications/all', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) throw new Error('Failed to load notifications');

            const data = await response.json();
            allNotifications = data.notifications || [];
            displayNotifications(allNotifications);

        } catch (error) {
            console.error('Error loading notifications:', error);
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <div class="empty-state-title">Error Loading Notifications</div>
                    <div class="empty-state-message">Please try refreshing the page</div>
                </div>
            `;
        }
    }

    function displayNotifications(notifications) {
        const container = document.getElementById('notificationsListFull');

        if (!notifications || notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🔔</div>
                    <div class="empty-state-title">No Notifications</div>
                    <div class="empty-state-message">You're all caught up!</div>
                </div>
            `;
            return;
        }

        container.innerHTML = notifications.map(notification => `
            <div class="notification-item-full ${notification.is_read ? '' : 'unread'}"
                 data-id="${notification.id}"
                 data-category="${getCategoryFromTitle(notification.title)}">
                <input type="checkbox" class="notification-checkbox"
                       onchange="toggleNotificationSelection(${notification.id})">
                <div class="notification-icon-large">
                    ${getNotificationIcon(notification.title)}
                </div>
                <div class="notification-body">
                    <div class="notification-header-row">
                        <div class="notification-title-full">
                            ${notification.title}
                            ${notification.is_read ? '' : '<span class="unread-dot"></span>'}
                        </div>
                        <div class="notification-time-full">
                            ${formatTimeAgo(notification.created_at)}
                        </div>
                    </div>
                    <div class="notification-message-full">
                        ${notification.message}
                    </div>
                    <div class="notification-actions-row">
                        ${notification.is_read ? '' : `
                            <button class="notification-action-btn" onclick="markSingleAsRead(${notification.id}, event)">
                                Mark as Read
                            </button>
                        `}
                        <button class="notification-action-btn delete" onclick="deleteSingle(${notification.id}, event)">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function filterNotifications(filter) {
        currentFilter = filter;

        // Update active button
        document.querySelectorAll('.filter-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

        // Filter notifications
        let filtered = allNotifications;

        if (filter === 'unread') {
            filtered = allNotifications.filter(n => !n.is_read);
        } else if (filter !== 'all') {
            filtered = allNotifications.filter(n => getCategoryFromTitle(n.title) === filter);
        }

        displayNotifications(filtered);
        selectedNotifications.clear();
        updateBulkActionsBar();
    }

    function getCategoryFromTitle(title) {
        if (title.includes('Stock') || title.includes('Inventory')) return 'stock';
        if (title.includes('Order')) return 'orders';
        if (title.includes('Sale')) return 'sales';
        return 'system';
    }

    function getNotificationIcon(title) {
        const icons = {
            'Low Stock Alert': '📦',
            'Out of Stock Alert': '🚨',
            'Stock Replenished': '✅',
            'New Order': '🛒',
            'Order Approved': '✅',
            'Order Completed': '🎉',
            'High Value Sale': '💰',
            'Product Expiration': '⏰',
            'Daily Sales Report': '📊',
            'System Message': '⚙️'
        };

        for (let key in icons) {
            if (title.includes(key)) return icons[key];
        }
        return '🔔';
    }

    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
        }
        if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours === 1 ? '' : 's'} ago`;
        }
        if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days === 1 ? '' : 's'} ago`;
        }
        return date.toLocaleDateString();
    }

    function toggleNotificationSelection(id) {
        if (selectedNotifications.has(id)) {
            selectedNotifications.delete(id);
        } else {
            selectedNotifications.add(id);
        }
        updateBulkActionsBar();
    }

    function updateBulkActionsBar() {
        const bar = document.getElementById('bulkActionsBar');
        const count = document.getElementById('selectedCount');

        if (selectedNotifications.size > 0) {
            bar.classList.add('active');
            count.textContent = `${selectedNotifications.size} selected`;
        } else {
            bar.classList.remove('active');
        }
    }

    function deselectAll() {
        selectedNotifications.clear();
        document.querySelectorAll('.notification-checkbox').forEach(cb => cb.checked = false);
        updateBulkActionsBar();
    }

    async function markSingleAsRead(id, event) {
        event.stopPropagation();

        try {
            const response = await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const notification = allNotifications.find(n => n.id === id);
                if (notification) notification.is_read = true;

                filterNotifications(currentFilter);
                showToast('Notification marked as read', 'success');
            }
        } catch (error) {
            showToast('Failed to mark as read', 'error');
        }
    }

    async function deleteSingle(id, event) {
        event.stopPropagation();

        if (!confirm('Delete this notification?')) return;

        try {
            const response = await fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                allNotifications = allNotifications.filter(n => n.id !== id);
                filterNotifications(currentFilter);
                showToast('Notification deleted', 'success');
            }
        } catch (error) {
            showToast('Failed to delete notification', 'error');
        }
    }

    async function markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                allNotifications.forEach(n => n.is_read = true);
                filterNotifications(currentFilter);
                showToast('All notifications marked as read', 'success');
            }
        } catch (error) {
            showToast('Failed to mark all as read', 'error');
        }
    }

    async function clearAllRead() {
        if (!confirm('Delete all read notifications?')) return;

        try {
            const response = await fetch('/notifications/clear-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                allNotifications = allNotifications.filter(n => !n.is_read);
                filterNotifications(currentFilter);
                showToast('Read notifications cleared', 'success');
            }
        } catch (error) {
            showToast('Failed to clear read notifications', 'error');
        }
    }

    async function bulkMarkAsRead() {
        const ids = Array.from(selectedNotifications);

        try {
            await Promise.all(ids.map(id =>
                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
            ));

            ids.forEach(id => {
                const notification = allNotifications.find(n => n.id === id);
                if (notification) notification.is_read = true;
            });

            deselectAll();
            filterNotifications(currentFilter);
            showToast('Selected notifications marked as read', 'success');
        } catch (error) {
            showToast('Failed to mark notifications as read', 'error');
        }
    }

    async function bulkDelete() {
        if (!confirm(`Delete ${selectedNotifications.size} notifications?`)) return;

        const ids = Array.from(selectedNotifications);

        try {
            const response = await fetch('/notifications/bulk-delete', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids })
            });

            if (response.ok) {
                allNotifications = allNotifications.filter(n => !ids.includes(n.id));
                deselectAll();
                filterNotifications(currentFilter);
                showToast('Selected notifications deleted', 'success');
            }
        } catch (error) {
            showToast('Failed to delete notifications', 'error');
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endsection
