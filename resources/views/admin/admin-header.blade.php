<header>
    <div class="header-top">
        <div class="logo">MJ'S PHARMACY</div>
    </div>
</header>

<!-- Add this CSRF token meta tag in the head section -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
<nav class="nav-bar">
    <div class="nav-links">
        <div class="nav-button-wrapper">
            <a href="{{ route('dashboard') }}" class="nav-button {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
            <span class="dashboard-alert-badge" id="dashboardAlertBadge" style="display: none;"></span>
        </div>
        <a href="{{ route('suppliers.index') }}" class="nav-button {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Suppliers</a>
        <a href="{{ route('products.index') }}" class="nav-button {{ request()->routeIs('products.*') ? 'active' : '' }}">Products</a>
        <a href="{{ route('inventory.index') }}" class="nav-button {{ request()->routeIs('inventory.*') ? 'active' : '' }}">Inventory</a>
        <a href="{{ route('orders.index') }}" class="nav-button {{ request()->routeIs('orders.*') ? 'active' : '' }}">Orders</a>
        <a href="{{ route('sales.index') }}" class="nav-button {{ request()->routeIs('sales.*') ? 'active' : '' }}">Sales</a>
        <a href="{{ route('customer.index') }}" class="nav-button {{ request()->routeIs('customer.*') ? 'active' : '' }}">Customer</a>
        <a href="{{ route('reports.index') }}" class="nav-button {{ request()->routeIs('reports.*') ? 'active' : '' }}">Reports</a>
        <a href="{{ route('POS.index') }}" class="nav-button {{ request()->routeIs('pos.*') ? 'active' : '' }}"></a>
    </div>

    <div class="header-actions">
        <div class="notifications-dropdown">
            <button class="notifications-toggle" aria-label="Notifications" id="notificationsToggle">
                <span class="notification-icon">🔔</span>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
            <div class="notifications-menu" id="notificationsMenu" style="display: none;">
                <div class="notifications-header">
                    <h3>Notifications</h3>
                    <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
                </div>
                <div class="notifications-list" id="notificationsList">
                    <!-- Loading state -->
                    <div class="notification-loading">
                        <div style="text-align: center; padding: 20px; color: #6b7280;">
                            <div style="margin-bottom: 10px;">🔄</div>
                            <div>Loading notifications...</div>
                        </div>
                    </div>
                </div>
                <div class="notifications-footer">
                    <a href="#" class="view-all-notifications">View All Notifications</a>
                </div>
            </div>
        </div>

        <!-- Settings Dropdown -->
        <div class="settings-dropdown">
            <button class="settings-toggle" aria-label="Settings">&#9776;</button>
            <div class="settings-menu" id="settingsMenu" style="display: none;">
                <div class="user-info">
                    @if(Auth::check() && Auth::user()->isAdmin())
                    @php
                    $user = Auth::user();
                    $nameParts = explode(' ', $user->name);
                    $firstName = $nameParts[0] ?? 'Admin';
                    $lastName = end($nameParts) ?? '';
                    $initials = strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($lastName, 0, 1));
                    @endphp
                    <div class="user-avatar">{{ $initials }}</div>
                    <div class="user-details">
                        <div class="user-name">{{ $user->name }}</div>
                        <div class="user-email">{{ $user->email }}</div>
                    </div>
                    @else
                    <div class="user-avatar">👤</div>
                    <div class="user-details">
                        <div class="user-name">Admin User</div>
                        <div class="user-email">admin@mjspharmacy.com</div>
                    </div>
                    @endif
                </div>
                <div class="menu-divider"></div>
                <a href="{{ route('admin.profile') }}" class="menu-item" style="text-decoration: none; color: inherit; display: block;">👤 Profile</a>
                <button class="menu-item">⚙️ Settings</button>
                <button class="menu-item" id="themeToggle">🌙 Dark Mode</button>
                <div class="menu-divider"></div>
                <button class="menu-item logout-item" onclick="showLogoutModal()">🚪 Logout</button>
            </div>
        </div>
    </div>
</nav>

{{-- Logout Modal --}}
<div id="logoutModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Logout Notice</h2>
        <p>You are about to log out of your session. Do you want to proceed?</p>
        <div class="logout-modal-buttons">
            <form action="{{ url('/admin/login') }}" method="GET">
                <button type="submit" class="lgt-btn-confirm">Logout</button>
            </form>
            <button class="lgt-btn-cancel" onclick="hideLogoutModal()">Cancel</button>
        </div>
    </div>
</div>

@push('scripts')

<script>
    (() => {
        const toggle = document.getElementById('themeToggle');
        const body = document.body;

        if (toggle) {
            toggle.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                toggle.textContent = body.classList.contains('dark-mode') ? '☀️ Light Mode' : '🌙 Dark Mode';
                localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
                if (toggle) toggle.textContent = '☀️ Light Mode';
            }
            loadNotifications();
            checkInventoryAlerts(); // Check for inventory alerts on load
        });


        // Updated checkInventoryAlerts function using CSS classes
        async function checkInventoryAlerts() {
            const dashboardBadge = document.getElementById('dashboardAlertBadge');

            if (!dashboardBadge) {
                console.error('Dashboard alert badge element not found!');
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch('/inventory-alerts-check', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();

                    // Strict condition checking
                    const hasActualAlerts = data.has_alerts === true &&
                        data.total_count > 0 &&
                        data.counts &&
                        (data.counts.out_of_stock > 0 ||
                            data.counts.low_stock > 0 ||
                            data.counts.expiring > 0);

                    if (hasActualAlerts) {
                        // Show badge using CSS class
                        dashboardBadge.classList.add('visible');
                        dashboardBadge.classList.remove('hidden');
                        dashboardBadge.textContent = '!';

                        // Add critical class for out of stock or critically low items
                        if (data.critical_count > 0) {
                            dashboardBadge.classList.add('critical');
                        } else {
                            dashboardBadge.classList.remove('critical');
                        }

                        // Update tooltip with specific info
                        const messages = [];
                        if (data.counts.out_of_stock > 0) {
                            messages.push(`${data.counts.out_of_stock} out of stock`);
                        }
                        if (data.counts.low_stock > 0) {
                            messages.push(`${data.counts.low_stock} low stock`);
                        }
                        if (data.counts.expiring > 0) {
                            messages.push(`${data.counts.expiring} expiring soon`);
                        }

                        dashboardBadge.title = `Inventory Alert: ${messages.join(', ')}`;
                    } else {
                        // Hide badge using CSS classes
                        dashboardBadge.classList.remove('visible');
                        dashboardBadge.classList.add('hidden');
                        dashboardBadge.classList.remove('critical');
                        dashboardBadge.title = '';
                        dashboardBadge.textContent = '';
                    }
                }
            } catch (error) {
                console.error('Error checking inventory alerts:', error);
            }
        }

        // Auto-check inventory alerts every 30 seconds
        setInterval(checkInventoryAlerts, 30000);

        window.checkInventoryAlerts = checkInventoryAlerts;
        checkInventoryAlerts();

        
        document.getElementById('notificationsToggle')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = document.getElementById('notificationsMenu');
            const settingsMenu = document.getElementById('settingsMenu');

            if (settingsMenu) settingsMenu.style.display = 'none';

            if (menu) {
                const isVisible = menu.style.display === 'block';
                menu.style.display = isVisible ? 'none' : 'block';

                if (!isVisible) {
                    loadNotifications();
                }
            }
        });

        document.querySelector('.settings-toggle')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = document.getElementById('settingsMenu');
            const notificationsMenu = document.getElementById('notificationsMenu');

            if (notificationsMenu) notificationsMenu.style.display = 'none';

            if (menu) {
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-dropdown')) {
                const notificationsMenu = document.getElementById('notificationsMenu');
                if (notificationsMenu) notificationsMenu.style.display = 'none';
            }

            if (!e.target.closest('.settings-dropdown')) {
                const settingsMenu = document.getElementById('settingsMenu');
                if (settingsMenu) settingsMenu.style.display = 'none';
            }
        });

        async function loadNotifications() {
            const notificationsList = document.getElementById('notificationsList');
            const notificationBadge = document.getElementById('notificationBadge');

            try {
                if (notificationsList) {
                    notificationsList.innerHTML = `
                    <div class="notification-loading">
                        <div style="text-align: center; padding: 20px; color: #6b7280;">
                            <div style="margin-bottom: 10px;">🔄</div>
                            <div>Loading notifications...</div>
                        </div>
                    </div>
                `;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch('/notifications', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('Notification data received:', data);

                const transformedNotifications = data.notifications ? data.notifications.map(notification => ({
                    id: notification.id,
                    title: notification.title,
                    message: notification.message,
                    time: formatTimeAgo(notification.created_at),
                    unread: !notification.is_read,
                    avatar: getNotificationIcon(notification.title)
                })) : [];

                displayNotifications(transformedNotifications, data.unread_count || 0);

            } catch (error) {
                console.error('Error loading notifications:', error);

                if (notificationsList) {
                    notificationsList.innerHTML = `
                    <div class="notification-error">
                        <div style="text-align: center; padding: 30px; color: #ef4444;">
                            <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
                            <div style="font-weight: 500; margin-bottom: 5px;">Failed to load notifications</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Please check your connection and try again</div>
                            <button onclick="loadNotifications()" style="margin-top: 15px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                Retry
                            </button>
                        </div>
                    </div>
                `;
                }

                if (notificationBadge) {
                    notificationBadge.textContent = '!';
                    notificationBadge.style.display = 'flex';
                    notificationBadge.style.backgroundColor = '#ef4444';
                }
            }
        }

        function getNotificationIcon(title) {
            const iconMap = {
                'Low Stock Alert': '📦',
                'Out of Stock Alert': '🚨',
                'Stock Replenished ✅': '✅',
                'New Order Received': '🛒',
                'Order Approved': '✅',
                'Order Completed': '🎉',
                'High Value Sale': '💰',
                'Product Expiration Warning': '⏰',
                'Daily Sales Report': '📊',
                'System Message': '⚙️'
            };

            return iconMap[title] || '🔔';
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) {
                return 'just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours} hour${hours === 1 ? '' : 's'} ago`;
            } else if (diffInSeconds < 604800) {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days} day${days === 1 ? '' : 's'} ago`;
            } else {
                return date.toLocaleDateString();
            }
        }

        function displayNotifications(notifications, unreadCount) {
            const notificationsList = document.getElementById('notificationsList');
            const notificationBadge = document.getElementById('notificationBadge');

            console.log('Displaying notifications:', notifications, 'Unread count:', unreadCount);

            if (!notificationsList) {
                console.error('Notifications list element not found');
                return;
            }

            if (notificationBadge) {
                notificationBadge.textContent = unreadCount;
                notificationBadge.style.display = unreadCount > 0 ? 'flex' : 'none';
                notificationBadge.style.backgroundColor = '#3b82f6';
            }

            notificationsList.innerHTML = '';

            if (!notifications || notifications.length === 0) {
                notificationsList.innerHTML = `
                <div class="no-notifications">
                    <div style="text-align: center; padding: 30px; color: #6b7280;">
                        <div style="font-size: 48px; margin-bottom: 10px;">🔔</div>
                        <div style="font-weight: 500; margin-bottom: 5px;">No notifications</div>
                        <div style="font-size: 0.875rem;">You're all caught up!</div>
                    </div>
                </div>
            `;
                return;
            }

            notifications.forEach(notification => {
                const notificationElement = document.createElement('div');
                notificationElement.className = `notification-item ${notification.unread ? 'unread' : ''}`;

                notificationElement.onclick = async (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    if (notification.unread) {
                        const success = await markAsRead(notification.id);
                        if (success) {
                            notification.unread = false;
                            notificationElement.classList.remove('unread');

                            const unreadIndicator = notificationElement.querySelector('.unread-indicator');
                            if (unreadIndicator) {
                                unreadIndicator.remove();
                            }

                            const content = notificationElement.querySelector('.notification-content');
                            if (content) {
                                content.style.backgroundColor = '';
                            }

                            const currentBadge = document.getElementById('notificationBadge');
                            if (currentBadge) {
                                const currentCount = parseInt(currentBadge.textContent) || 0;
                                const newCount = Math.max(0, currentCount - 1);
                                currentBadge.textContent = newCount;
                                currentBadge.style.display = newCount > 0 ? 'flex' : 'none';
                            }

                            notificationElement.style.cursor = 'default';
                        }
                    }
                };

                if (notification.unread) {
                    notificationElement.style.cursor = 'pointer';
                }

                notificationElement.innerHTML = `
                <div class="notification-content" style="display: flex; padding: 12px; border-bottom: 1px solid #f3f4f6; ${notification.unread ? 'background-color: #eff6ff;' : ''} transition: all 0.2s ease;">
                    <div class="notification-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 18px;">
                        ${notification.avatar}
                    </div>
                    <div class="notification-details" style="flex: 1; min-width: 0;">
                        <div class="notification-title" style="font-weight: 600; font-size: 0.875rem; color: #111827; margin-bottom: 2px; ${notification.unread ? 'color: #1d4ed8;' : ''} display: flex; align-items: center; gap: 4px;">
                            ${notification.title}
                            ${notification.unread ? '<span class="unread-indicator" style="color: #3b82f6; font-size: 8px;">●</span>' : ''}
                        </div>
                        <div class="notification-message" style="font-size: 0.8125rem; color: #6b7280; line-height: 1.4; margin-bottom: 4px; word-wrap: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            ${notification.message}
                        </div>
                        <div class="notification-time" style="font-size: 0.75rem; color: #9ca3af;">
                            ${notification.time}
                        </div>
                    </div>
                    ${notification.unread ? '<div style="width: 4px; height: 4px; background: #3b82f6; border-radius: 50%; margin: auto 0;"></div>' : ''}
                </div>
            `;

                notificationsList.appendChild(notificationElement);
            });
        }

        async function markAsRead(notificationId) {
            try {
                console.log('Marking notification as read:', notificationId);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch(`/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const result = await response.json();
                    console.log('Successfully marked as read:', result);
                    return true;
                } else {
                    console.error('Failed to mark notification as read:', response.status, response.statusText);
                    return false;
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
                return false;
            }
        }

        window.markAllAsRead = async function() {
            try {
                console.log('Marking all notifications as read');

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const result = await response.json();
                    console.log('Successfully marked all as read:', result);

                    const notificationBadge = document.getElementById('notificationBadge');
                    if (notificationBadge) {
                        notificationBadge.style.display = 'none';
                    }

                    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
                    unreadNotifications.forEach(item => {
                        item.classList.remove('unread');
                        item.style.cursor = 'default';

                        const unreadIndicator = item.querySelector('.unread-indicator');
                        if (unreadIndicator) {
                            unreadIndicator.remove();
                        }

                        const content = item.querySelector('.notification-content');
                        if (content) {
                            content.style.backgroundColor = '';
                        }

                        const blueDot = item.querySelector('[style*="background: #3b82f6"]');
                        if (blueDot) {
                            blueDot.remove();
                        }
                    });

                    showToast('All notifications marked as read', 'success');

                } else {
                    console.error('Failed to mark all notifications as read:', response.status, response.statusText);
                    showToast('Failed to mark notifications as read', 'error');
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
                showToast('Error occurred while marking notifications as read', 'error');
            }
        };

        function showToast(message, type = 'info') {
            const existingToast = document.querySelector('.notification-toast');
            if (existingToast) {
                existingToast.remove();
            }

            const toast = document.createElement('div');
            toast.className = 'notification-toast';
            toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        `;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 10);

            setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 3000);
        }

        window.showLogoutModal = function() {
            const modal = document.getElementById("logoutModal");
            if (modal) modal.classList.add("active");
        };

        window.hideLogoutModal = function() {
            const modal = document.getElementById("logoutModal");
            if (modal) modal.classList.remove("active");
        };

        // Auto-refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);

        window.loadNotifications = loadNotifications;
    })();
</script>
@endpush