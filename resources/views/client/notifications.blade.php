{{-- resources/views/client/notifications.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications - MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/notification.css') }}">
</head>

<body>
    @include('client.client-header')
    
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h2>📢 Notifications</h2>
                <p>Stay up-to-date with the latest updates from MJ's Pharmacy</p>
            </div>
        </div>

        <div class="notification-container">
            @php
                $unreadCount = $notifications->where('is_read', false)->count();
                $totalCount = $notifications->count();
            @endphp

            <div class="stats-header">
                <h3>Your Notifications</h3>
                <div style="display: flex; gap: 15px;">
                    <span class="notification-count">{{ $totalCount }} Total</span>
                    @if($unreadCount > 0)
                        <span class="unread-count">{{ $unreadCount }} Unread</span>
                    @endif
                </div>
            </div>

            @if($unreadCount > 0)
                <div class="notifications-actions" style="margin-bottom: 30px;">
                    <button class="mark-read-btn" onclick="markAllAsRead()" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        ✓ Mark all as read
                    </button>
                </div>
            @endif

            <div class="notification-list">
                @forelse ($notifications as $notification)
                    <div class="notification-item {{ !$notification->is_read ? 'unread' : '' }}" 
                         data-id="{{ $notification->id }}"
                         @if(!$notification->is_read) style="cursor: pointer;" onclick="markAsRead({{ $notification->id }})" @endif>
                        
                        <div class="notification-header">
                            <h4 class="notification-title">
                                @switch($notification->type)
                                    @case('order_received')
                                        📨 
                                        @break
                                    @case('order_approved')
                                        ✅ 
                                        @break
                                    @case('order_partially_approved')
                                        ⚠️ 
                                        @break
                                    @case('order_ready')
                                        🎉 
                                        @break
                                    @case('order_cancelled')
                                        ❌ 
                                        @break
                                    @case('order_delayed')
                                        ⏰ 
                                        @break
                                    @default
                                        🔔 
                                @endswitch
                                {{ $notification->title }}
                            </h4>
                            <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        
                        <p class="notification-description">{{ $notification->message }}</p>

                        @if($notification->prescription)
                            <div class="prescription-meta" style="margin-top: 15px; padding: 12px; background: #f8fdff; border-radius: 10px; border-left: 4px solid #667eea;">
                                <span style="font-size: 14px; color: #667eea; font-weight: 600;">
                                    📋 Prescription #P{{ $notification->prescription->id }}
                                </span>
                                @if($notification->prescription->status)
                                    <span class="status-badge" style="margin-left: 10px; padding: 4px 12px; background: #e3f2fd; color: #1976d2; border-radius: 15px; font-size: 12px; font-weight: 500;">
                                        {{ ucfirst($notification->prescription->status) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        @if (!$notification->is_read)
                            <div style="margin-top: 20px;">
                                <button class="mark-read-btn" type="button" onclick="event.stopPropagation(); markAsRead({{ $notification->id }})">
                                    ✓ Mark as Read
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="no-notifications">
                        <div class="no-notifications-icon">📭</div>
                        <h3>No notifications yet</h3>
                        <p>You're all caught up! When you place prescription orders, you'll receive updates here about their status.</p>
                        <div style="margin-top: 30px;">
                            <a href="{{ url('/home/uploads') }}" class="mark-read-btn" style="text-decoration: none; display: inline-block;">
                                📝 Submit Prescription
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div style="margin-top: 40px; display: flex; justify-content: center;">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        // Mark individual notification as read
        async function markAsRead(notificationId) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch(`/home/notifications/read/${notificationId}`, {
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
                    const notificationCard = document.querySelector(`[data-id="${notificationId}"]`);
                    if (notificationCard) {
                        notificationCard.classList.remove('unread');
                        notificationCard.removeAttribute('onclick');
                        notificationCard.style.cursor = 'default';
                        
                        // Remove mark as read button
                        const actionBtn = notificationCard.querySelector('.mark-read-btn');
                        if (actionBtn) {
                            actionBtn.remove();
                        }
                    }
                    
                    // Update counts and hide mark all button if needed
                    updateUIAfterMarkingRead();
                } else {
                    console.error('Failed to mark notification as read');
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // Mark all notifications as read
        async function markAllAsRead() {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch('/home/notifications/mark-all-read', {
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
                    // Remove unread class from all notifications
                    document.querySelectorAll('.notification-item.unread').forEach(card => {
                        card.classList.remove('unread');
                        card.removeAttribute('onclick');
                        card.style.cursor = 'default';
                        
                        // Remove action buttons
                        const actionBtn = card.querySelector('.mark-read-btn');
                        if (actionBtn) {
                            actionBtn.remove();
                        }
                    });
                    
                    // Hide the "Mark all as read" button
                    const markAllBtn = document.querySelector('.notifications-actions');
                    if (markAllBtn) {
                        markAllBtn.style.display = 'none';
                    }
                    
                    // Update unread count display
                    const unreadCountSpan = document.querySelector('.unread-count');
                    if (unreadCountSpan) {
                        unreadCountSpan.style.display = 'none';
                    }

                    // Update header notification badge if exists
                    updateHeaderBadge(0);
                } else {
                    console.error('Failed to mark all notifications as read');
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        }

        // Update UI after marking notifications as read
        function updateUIAfterMarkingRead() {
            const unreadCards = document.querySelectorAll('.notification-item.unread').length;
            
            // Update unread count display
            const unreadCountSpan = document.querySelector('.unread-count');
            if (unreadCountSpan) {
                if (unreadCards > 0) {
                    unreadCountSpan.textContent = `${unreadCards} Unread`;
                } else {
                    unreadCountSpan.style.display = 'none';
                }
            }
            
            // Hide mark all button if no unread notifications
            const markAllBtn = document.querySelector('.notifications-actions');
            if (markAllBtn && unreadCards === 0) {
                markAllBtn.style.display = 'none';
            }

            // Update header notification badge
            updateHeaderBadge(unreadCards);
        }

        // Update header notification badge
        function updateHeaderBadge(count) {
            const headerBadge = document.querySelector('#notificationBadge');
            if (headerBadge) {
                if (count > 0) {
                    headerBadge.textContent = count;
                    headerBadge.style.display = 'flex';
                } else {
                    headerBadge.style.display = 'none';
                }
            }
        }
    </script>
    
    @stack('scripts')
</body>

</html>