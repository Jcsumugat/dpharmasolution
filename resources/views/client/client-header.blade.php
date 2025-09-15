{{-- client-header.blade.php --}}
<header>
    <div class="header-container">
        <div class="logo">MJ'S PHARMACY</div>
        <div class="user-profile">
            <div class="user-info" onclick="toggleUserDropdown()">
                <div class="user-avatar">
                    <span id="userInitials">
                        @if(Auth::guard('customer')->check())
                            {{ strtoupper(substr(Auth::guard('customer')->user()->full_name, 0, 1)) }}{{ strtoupper(substr(strrchr(Auth::guard('customer')->user()->full_name, ' '), 1, 1)) }}
                        @else
                            GU
                        @endif
                    </span>
                </div>
                <div class="user-details">
                    <div class="user-name" id="userName">
                        @if(Auth::guard('customer')->check())
                            {{ explode(' ', Auth::guard('customer')->user()->full_name)[0] }} {{ explode(' ', Auth::guard('customer')->user()->full_name)[count(explode(' ', Auth::guard('customer')->user()->full_name)) - 1] }}
                        @else
                            Guest User
                        @endif
                    </div>
                    <div class="user-email" id="userEmail">
                        @if(Auth::guard('customer')->check())
                            {{ Auth::guard('customer')->user()->email_address }}
                        @else
                            guest@example.com
                        @endif
                    </div>
                </div>
                <div class="dropdown-arrow">▼</div>
            </div>

            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="dropdown-user-name" id="dropdownUserName">
                        @if(Auth::guard('customer')->check())
                            {{ Auth::guard('customer')->user()->full_name }}
                        @else
                            Guest User
                        @endif
                    </div>
                    <div class="dropdown-user-email" id="dropdownUserEmail">
                        @if(Auth::guard('customer')->check())
                            {{ Auth::guard('customer')->user()->email_address }}
                        @else
                            guest@example.com
                        @endif
                    </div>
                </div>

                @if(Auth::guard('customer')->check())
                    <a href="{{ url('/profile') }}" class="dropdown-item">
                        <span class="dropdown-icon">👤</span>
                        <span>My Profile</span>
                    </a>

                    <a href="{{ url('/home/uploads') }}" class="dropdown-item">
                        <span class="dropdown-icon">📦</span>
                        <span>My Orders</span>
                    </a>

                    <a href="{{ url('/settings') }}" class="dropdown-item">
                        <span class="dropdown-icon">⚙️</span>
                        <span>Settings</span>
                    </a>

                    <button class="dropdown-item logout" onclick="showLogoutModal()">
                        <span class="dropdown-icon">🚪</span>
                        <span>Logout</span>
                    </button>
                @else
                    <a href="{{ route('login.form') }}" class="dropdown-item">
                        <span class="dropdown-icon">🔑</span>
                        <span>Login</span>
                    </a>

                    <a href="{{ route('signup.step_one') }}" class="dropdown-item">
                        <span class="dropdown-icon">📝</span>
                        <span>Sign Up</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>

<link rel="stylesheet" href="{{ asset('css/customer/header.css') }}" />

<nav class="nav-buttons">
    <div class="header-right">
        <a href="{{ url('/home') }}" class="nav-button {{ request()->is('home') ? 'active' : '' }}">Home</a>
        <a href="{{ url('/home/uploads') }}" class="nav-button {{ request()->is('home/uploads') ? 'active' : '' }}">Order</a>
        <a href="{{ url('/home/products') }}" class="nav-button {{ request()->is('home/products') ? 'active' : '' }}">Products</a>

        {{-- Notifications button with badge --}}
        <a href="{{ url('/home/notifications') }}" class="nav-button notifications-nav {{ request()->is('home/notifications') ? 'active' : '' }}">
            Notifications
            @if(Auth::guard('customer')->check())
                @php
                    $unreadCount = \App\Models\CustomerNotification::getUnreadCountForCustomer(Auth::guard('customer')->id());
                @endphp
                @if($unreadCount > 0)
                    <span id="notificationBadge" class="notification-badge">{{ $unreadCount }}</span>
                @else
                    <span id="notificationBadge" class="notification-badge" style="display: none;"></span>
                @endif
            @endif
        </a>

        <a href="{{ url('/home/contact-us') }}" class="nav-button {{ request()->is('home/contact-us') ? 'active' : '' }}">Contact us</a>
        <a href="{{ url('/home/messages') }}" class="nav-button {{ request()->is('home/messages') ? 'active' : '' }}">Messages</a>
    </div>
</nav>

{{-- Logout Modal --}}
@if(Auth::guard('customer')->check())
<div id="logoutModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Logout Notice</h2>
        <p>You are about to log out of your session. Do you want to proceed?</p>
        <div class="logout-modal-buttons">
            <form action="{{ route('customer.logout') }}" method="POST">
                @csrf
                <button type="submit" class="lgt-btn-confirm">Logout</button>
            </form>
            <button class="lgt-btn-cancel" onclick="hideLogoutModal()">Cancel</button>
        </div>
    </div>
</div>
@endif

{{-- Additional CSS for notification badge --}}
<style>
/* Notification Badge Styles */
.notifications-nav {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ff4757, #ff3742);
    color: white;
    border-radius: 50%;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4);
    border: 2px solid white;
    z-index: 10;
    animation: notificationPulse 2s infinite;
}

.notification-badge:empty {
    display: none !important;
}

/* Badge animation */
@keyframes notificationPulse {
    0% {
        transform: scale(1);
        box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4), 0 0 0 0 rgba(255, 71, 87, 0.7);
    }
    70% {
        transform: scale(1.05);
        box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4), 0 0 0 8px rgba(255, 71, 87, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4), 0 0 0 0 rgba(255, 71, 87, 0);
    }
}

/* Adjust nav-button to accommodate the badge */
.nav-button.notifications-nav {
    position: relative;
    padding: 8px 16px;
}

/* Optional: Different styles for different count ranges */
.notification-badge[data-count="1"],
.notification-badge[data-count="2"],
.notification-badge[data-count="3"],
.notification-badge[data-count="4"],
.notification-badge[data-count="5"],
.notification-badge[data-count="6"],
.notification-badge[data-count="7"],
.notification-badge[data-count="8"],
.notification-badge[data-count="9"] {
    min-width: 20px;
    font-size: 11px;
}

/* For double digits */
.notification-badge {
    min-width: 22px;
    padding: 0 4px;
}

/* When count is over 99, show 99+ */
.notification-badge[data-count="99+"] {
    min-width: 26px;
    font-size: 10px;
}

/* Hover effect for notification nav */
.nav-button.notifications-nav:hover .notification-badge {
    transform: scale(1.1);
    animation: none;
}

/* Active state adjustment */
.nav-button.notifications-nav.active .notification-badge {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
}
</style>

<script>
    (() => {
        // Theme management
        const toggle = document.getElementById('themeToggle');
        const body = document.body;

        if (toggle) {
            toggle.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                toggle.textContent = body.classList.contains('dark-mode') ? '☀️ Light Mode' : '🌙 Dark Mode';
                localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
            });
        }

        // Initialize theme
        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
                if (toggle) toggle.textContent = '☀️ Light Mode';
            }
        });

        // Settings dropdown toggle
        document.querySelector('.settings-toggle')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = document.getElementById('settingsMenu');
            const userDropdown = document.getElementById('userDropdown');

            if (menu) {
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            }

            // Close user dropdown if open
            if (userDropdown) {
                userDropdown.classList.remove('show');
                document.querySelector('.user-info')?.classList.remove('active');
            }
        });

        // User dropdown functions
        window.toggleUserDropdown = function(e) {
            if (e) e.stopPropagation();

            const userDropdown = document.getElementById('userDropdown');
            const userInfo = document.querySelector('.user-info');
            const settingsMenu = document.getElementById('settingsMenu');

            if (userDropdown && userInfo) {
                const isOpen = userDropdown.classList.contains('show');

                if (isOpen) {
                    userDropdown.classList.remove('show');
                    userInfo.classList.remove('active');
                } else {
                    userDropdown.classList.add('show');
                    userInfo.classList.add('active');

                    // Close settings menu if open
                    if (settingsMenu) {
                        settingsMenu.style.display = 'none';
                    }
                }
            }
        };

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.settings-dropdown')) {
                const settingsMenu = document.getElementById('settingsMenu');
                if (settingsMenu) settingsMenu.style.display = 'none';
            }

            if (!e.target.closest('.user-profile')) {
                const userDropdown = document.getElementById('userDropdown');
                const userInfo = document.querySelector('.user-info');
                if (userDropdown && userInfo) {
                    userDropdown.classList.remove('show');
                    userInfo.classList.remove('active');
                }
            }
        });

        // Logout modal functions
        window.showLogoutModal = function() {
            const modal = document.getElementById("logoutModal");
            if (modal) modal.classList.add("active");
        };

        window.hideLogoutModal = function() {
            const modal = document.getElementById("logoutModal");
            if (modal) modal.classList.remove("active");
        };

        // Close modal when clicking overlay
        document.getElementById('logoutModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });

        // Function to update notification badge
        window.updateNotificationBadge = function(count) {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                if (count > 0) {
                    // Show count, but if over 99, show 99+
                    const displayCount = count > 99 ? '99+' : count.toString();
                    badge.textContent = displayCount;
                    badge.style.display = 'flex';
                    badge.setAttribute('data-count', displayCount);
                } else {
                    badge.style.display = 'none';
                    badge.textContent = '';
                }
            }
        };

        // Optional: Periodic check for new notifications (every 30 seconds)
        setInterval(() => {
            fetch('/home/notifications/unread-count', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.unread_count);
                }
            })
            .catch(error => {
                console.error('Error checking notification count:', error);
            });
        }, 30000);
    })();
</script>
