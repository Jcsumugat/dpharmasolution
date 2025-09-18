{{-- client-header.blade.php --}}
<header>
    <div class="header-container">
        <!-- Hamburger Menu (Mobile Only) -->
        <div class="hamburger" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </div>

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

<!-- Mobile Sidebar (Hidden on Desktop) -->
<div class="sidebar-overlay" onclick="closeSidebar()"></div>
<div class="mobile-sidebar" id="mobileSidebar">
    <nav class="sidebar-nav">
        <a href="{{ url('/home') }}" class="sidebar-nav-item {{ request()->is('home') ? 'active' : '' }}">
            <div class="nav-icon">🏠</div>
            <span>Home</span>
        </a>
        <a href="{{ url('/home/uploads') }}" class="sidebar-nav-item {{ request()->is('home/uploads') ? 'active' : '' }}">
            <div class="nav-icon">📋</div>
            <span>Order</span>
        </a>
        <a href="{{ url('/home/products') }}" class="sidebar-nav-item {{ request()->is('home/products') ? 'active' : '' }}">
            <div class="nav-icon">💊</div>
            <span>Products</span>
        </a>
        <a href="{{ url('/home/notifications') }}" class="sidebar-nav-item {{ request()->is('home/notifications') ? 'active' : '' }}">
            <div class="nav-icon">🔔</div>
            <span>Notifications</span>
            @if(Auth::guard('customer')->check())
                @php
                    $unreadCount = \App\Models\CustomerNotification::getUnreadCountForCustomer(Auth::guard('customer')->id());
                @endphp
                @if($unreadCount > 0)
                    <span class="notification-badge">{{ $unreadCount }}</span>
                @endif
            @endif
        </a>
        <a href="{{ url('/home/contact-us') }}" class="sidebar-nav-item {{ request()->is('home/contact-us') ? 'active' : '' }}">
            <div class="nav-icon">📞</div>
            <span>Contact us</span>
        </a>
        <a href="{{ url('/home/messages') }}" class="sidebar-nav-item {{ request()->is('home/messages') ? 'active' : '' }}">
            <div class="nav-icon">💬</div>
            <span>Messages</span>
        </a>
    </nav>
</div>

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
        });

        document.querySelector('.settings-toggle')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = document.getElementById('settingsMenu');
            const userDropdown = document.getElementById('userDropdown');

            if (menu) {
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            }

            if (userDropdown) {
                userDropdown.classList.remove('show');
                document.querySelector('.user-info')?.classList.remove('active');
            }
        });

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

                    if (settingsMenu) {
                        settingsMenu.style.display = 'none';
                    }
                }
            }
        };

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

            if (!e.target.closest('.mobile-sidebar') && !e.target.closest('.hamburger')) {
                closeSidebar();
            }
        });

        window.showLogoutModal = function() {
            const modal = document.getElementById("logoutModal");
            if (modal) modal.classList.add("active");
            closeSidebar();
        };

        window.hideLogoutModal = function() {
            const modal = document.getElementById("logoutModal");
            if (modal) modal.classList.remove("active");
        };

        document.getElementById('logoutModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });

        window.updateNotificationBadge = function(count) {
            const badge = document.getElementById('notificationBadge');
            const sidebarBadges = document.querySelectorAll('.mobile-sidebar .notification-badge');

            [badge, ...sidebarBadges].forEach(badgeEl => {
                if (badgeEl) {
                    if (count > 0) {
                        const displayCount = count > 99 ? '99+' : count.toString();
                        badgeEl.textContent = displayCount;
                        badgeEl.style.display = 'flex';
                        badgeEl.setAttribute('data-count', displayCount);
                    } else {
                        badgeEl.style.display = 'none';
                        badgeEl.textContent = '';
                    }
                }
            });
        };

        window.toggleSidebar = function() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const hamburger = document.querySelector('.hamburger');

            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            hamburger.classList.toggle('active');
        };

        window.closeSidebar = function() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const hamburger = document.querySelector('.hamburger');

            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            hamburger.classList.remove('active');
        };

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

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
