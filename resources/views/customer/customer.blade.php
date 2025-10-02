<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Digital Pharma</title>
    <link rel="stylesheet" href="{{ asset('css/admincustomer.css') }}" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    @include('admin.admin-header')

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Customer Management</h1>
            <p class="header-subtitle">Manage and monitor customer accounts across your platform</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="totalCount">{{ $customers->count() }}</div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number" id="activeCount">{{ $customers->where('status', 'active')->count() }}</div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon restricted">
                    <i class="fas fa-user-lock"></i>
                </div>
                <div class="stat-number" id="restrictedCount">{{ $customers->where('status', 'restricted')->count() }}
                </div>
                <div class="stat-label">Restricted</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon inactive">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-number" id="inactiveCount">{{ $customers->where('status', 'deactivated')->count() }}
                </div>
                <div class="stat-label">Deactivated</div>
            </div>
        </div>

        <div id="alert-container"></div>

        <div class="controls-section">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" class="search-input"
                    placeholder="Search by email or phone number...">
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Customer Directory</h2>
            </div>
            <table id="customers-table">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Customer Details</th>
                        <th>Registration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr data-customer-id="{{ $customer->id }}" data-status="{{ $customer->status }}"
                            data-auto-restore="{{ $customer->auto_restore_at ?? '' }}"
                            data-customer-email="{{ $customer->email_address }}"
                            data-customer-phone="{{ $customer->contact_number }}"
                            data-customer-address="{{ $customer->address }}"
                            data-customer-number="{{ $customer->customer_id }}">
                            <td><span class="customer-id">{{ $customer->customer_id }}</span></td>
                            <td>
                                <div class="customer-name">Customer #{{ $customer->id }}</div>
                                <button type="button" class="view-details-link view-customer-details">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </td>
                            <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                            <td>
                                @switch($customer->status)
                                    @case('active')
                                        <span class="status-badge status-active">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    @break

                                    @case('restricted')
                                        <span class="status-badge status-restricted">
                                            <i class="fas fa-exclamation-triangle"></i> Restricted
                                        </span>
                                        @if ($customer->auto_restore_at)
                                            <span class="auto-restore-timer"
                                                data-restore-time="{{ $customer->auto_restore_at }}">
                                                <i class="fas fa-clock"></i>
                                                <span class="timer-text">Calculating...</span>
                                            </span>
                                        @endif
                                    @break

                                    @case('deactivated')
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-pause-circle"></i> Deactivated
                                        </span>
                                        @if ($customer->auto_restore_at)
                                            <span class="auto-restore-timer"
                                                data-restore-time="{{ $customer->auto_restore_at }}">
                                                <i class="fas fa-clock"></i>
                                                <span class="timer-text">Calculating...</span>
                                            </span>
                                        @endif
                                    @break

                                    @default
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-question-circle"></i> {{ ucfirst($customer->status) }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="actions">
                                @if ($customer->status === 'active')
                                    <!-- Active: Show Restrict + Deactivate -->
                                    <button class="btn-action btn-restrict" data-customer-id="{{ $customer->id }}"
                                        data-action="restrict">
                                        <i class="fas fa-lock"></i> Restrict
                                    </button>
                                    <button class="btn-action btn-deactivate" data-customer-id="{{ $customer->id }}"
                                        data-action="toggle-activation">
                                        <i class="fas fa-pause"></i> Deactivate
                                    </button>
                                    <button class="btn-action btn-delete" data-customer-id="{{ $customer->id }}"
                                        data-action="delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                @endif

                                @if ($customer->status === 'restricted')
                                    <!-- Restricted: Show Unrestrict + Delete -->
                                    <button class="btn-action btn-unrestrict" data-customer-id="{{ $customer->id }}"
                                        data-action="restrict">
                                        <i class="fas fa-unlock"></i> Unrestrict
                                    </button>
                                    <button class="btn-action btn-delete" data-customer-id="{{ $customer->id }}"
                                        data-action="delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                @endif

                                @if ($customer->status === 'deactivated')
                                    <!-- Deactivated: Show Activate + Delete -->
                                    <button class="btn-action btn-activate" data-customer-id="{{ $customer->id }}"
                                        data-action="toggle-activation">
                                        <i class="fas fa-play"></i> Activate
                                    </button>
                                    <button class="btn-action btn-delete" data-customer-id="{{ $customer->id }}"
                                        data-action="delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-users-slash"></i>
                                    </div>
                                    <div class="empty-title">No customers found</div>
                                    <div class="empty-subtitle">No customers are registered yet</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL -->
        <div id="customerModal"
            style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); animation: fadeIn 0.2s ease;">
            <div
                style="background: white; border-radius: 16px; width: 90%; max-width: 550px; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25); animation: slideUp 0.3s ease;">
                <!-- Header -->
                <div
                    style="padding: 28px 32px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h3
                        style="margin: 0; font-size: 22px; font-weight: 700; color: white; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-user-circle" style="font-size: 26px;"></i> Customer Information
                    </h3>
                    <button onclick="closeCustomerModal()"
                        style="background: rgba(255, 255, 255, 0.2); border: none; font-size: 20px; color: white; cursor: pointer; padding: 0; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Body -->
                <div style="padding: 32px; background: #fafbfc;">
                    <!-- Customer ID -->
                    <div
                        style="margin-bottom: 24px; background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #667eea; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <div
                            style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #667eea; margin-bottom: 8px; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-hashtag"></i> Customer ID
                        </div>
                        <div id="modalCustomerId"
                            style="font-size: 16px; color: #1e293b; font-weight: 600; word-break: break-word; font-family: 'Courier New', monospace; background: #f8fafc; padding: 8px 12px; border-radius: 6px;">
                            -</div>
                    </div>

                    <!-- Email -->
                    <div
                        style="margin-bottom: 24px; background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <div
                            style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #10b981; margin-bottom: 8px; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-envelope"></i> Email Address
                        </div>
                        <div id="modalEmail"
                            style="font-size: 15px; color: #1e293b; line-height: 1.6; word-break: break-word;">-</div>
                    </div>

                    <!-- Phone -->
                    <div
                        style="margin-bottom: 24px; background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <div
                            style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #f59e0b; margin-bottom: 8px; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-phone"></i> Contact Number
                        </div>
                        <div id="modalPhone"
                            style="font-size: 15px; color: #1e293b; line-height: 1.6; word-break: break-word; font-weight: 500;">
                            -</div>
                    </div>

                    <!-- Address -->
                    <div
                        style="margin-bottom: 0; background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <div
                            style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #ef4444; margin-bottom: 8px; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </div>
                        <div id="modalAddress"
                            style="font-size: 15px; color: #1e293b; line-height: 1.6; word-break: break-word;">-</div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    style="padding: 20px 32px; background: #f8fafc; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end;">
                    <button onclick="closeCustomerModal()"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <script>
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let activeRequests = 0;
            const MAX_CONCURRENT_REQUESTS = 3;

            function sanitizeText(text) {
                if (!text || text === 'null' || text === 'undefined') return '-';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function canMakeRequest() {
                return activeRequests < MAX_CONCURRENT_REQUESTS;
            }

            // MODAL FUNCTIONS
            function closeCustomerModal() {
                const modal = document.getElementById('customerModal');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            function openCustomerModal(customerId, email, phone, address) {
                document.getElementById('modalCustomerId').textContent = sanitizeText(customerId);
                document.getElementById('modalEmail').textContent = sanitizeText(email);
                document.getElementById('modalPhone').textContent = sanitizeText(phone);
                document.getElementById('modalAddress').textContent = sanitizeText(address);

                const modal = document.getElementById('customerModal');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            // VIEW DETAILS CLICK HANDLER
            document.addEventListener('click', function(e) {
                if (e.target.closest('.view-customer-details')) {
                    e.preventDefault();
                    const button = e.target.closest('.view-customer-details');
                    const row = button.closest('tr');

                    if (row) {
                        const customerId = row.getAttribute('data-customer-number');
                        const email = row.getAttribute('data-customer-email');
                        const phone = row.getAttribute('data-customer-phone');
                        const address = row.getAttribute('data-customer-address');

                        openCustomerModal(customerId, email, phone, address);
                    }
                }
            });

            // CLOSE ON BACKGROUND CLICK
            document.getElementById('customerModal').addEventListener('click', function(e) {
                if (e.target.id === 'customerModal') {
                    closeCustomerModal();
                }
            });

            // CLOSE ON ESC KEY
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('customerModal');
                    if (modal.style.display === 'flex') {
                        closeCustomerModal();
                    }
                }
            });


            // Timer functions
            function updateTimers() {
                const timers = document.querySelectorAll('.auto-restore-timer');
                const now = new Date();

                timers.forEach(timer => {
                    const restoreTime = new Date(timer.getAttribute('data-restore-time'));
                    const diff = restoreTime - now;

                    if (diff <= 0) {
                        showAlert('A customer status has been automatically restored. Refreshing page...', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                        return;
                    }

                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                    let timeText = '';
                    if (days > 0) {
                        timeText = `${days}d ${hours}h`;
                    } else if (hours > 0) {
                        timeText = `${hours}h ${minutes}m`;
                    } else {
                        timeText = `${minutes}m`;
                    }

                    const timerText = timer.querySelector('.timer-text');
                    if (timerText) {
                        timerText.textContent = `Auto-restore in ${timeText}`;
                    }
                });
            }

            function updateStats() {
                const rows = document.querySelectorAll('tbody tr[data-customer-id]');
                let total = 0,
                    active = 0,
                    deactivated = 0,
                    restricted = 0;

                rows.forEach(row => {
                    if (row.style.display === 'none') return;

                    total++;
                    const status = row.getAttribute('data-status');
                    switch (status) {
                        case 'active':
                            active++;
                            break;
                        case 'deactivated':
                            deactivated++;
                            break;
                        case 'restricted':
                            restricted++;
                            break;
                    }
                });

                document.getElementById('totalCount').textContent = total;
                document.getElementById('activeCount').textContent = active;
                document.getElementById('inactiveCount').textContent = deactivated;
                document.getElementById('restrictedCount').textContent = restricted;
            }

            function showLoading() {
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.add('show');
                }
            }

            function hideLoading() {
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('show');
                }
            }

            function showAlert(message, type = 'success') {
                const alertContainer = document.getElementById('alert-container');
                if (!alertContainer) return;

                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type}`;

                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                alertDiv.innerHTML = `<i class="fas ${icon}"></i>${sanitizeText(message)}`;

                alertContainer.appendChild(alertDiv);

                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 5000);
            }

            document.addEventListener('click', function(e) {
                const actionButton = e.target.closest('button[data-action]');
                if (actionButton && !actionButton.disabled) {
                    const customerId = parseInt(actionButton.getAttribute('data-customer-id'));
                    const action = actionButton.getAttribute('data-action');

                    if (!canMakeRequest()) {
                        showAlert('Too many requests. Please wait a moment.', 'error');
                        return;
                    }

                    switch (action) {
                        case 'restrict':
                            restrictAccount(customerId, actionButton);
                            break;
                        case 'toggle-activation':
                            toggleActivation(actionButton, customerId);
                            break;
                        case 'delete':
                            deleteAccount(customerId);
                            break;
                    }
                }
            });

            function restrictAccount(customerId, button) {
                const isRestricted = button.textContent.includes('Unrestrict');
                const action = isRestricted ? 'remove restriction from' : 'restrict';

                const confirmed = confirm(
                    `Are you sure you want to ${action} this customer?${!isRestricted ? '\n\nThe customer will be automatically restored to active status after 3 days.' : ''}`
                );
                if (!confirmed) return;

                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                activeRequests++;

                fetch(`/admin/customers/${customerId}/restrict`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message || 'Status updated successfully', 'success');

                            const newStatus = data.status;
                            const row = button.closest('tr');

                            button.innerHTML = newStatus === 'restricted' ?
                                '<i class="fas fa-unlock"></i> Unrestrict' :
                                '<i class="fas fa-lock"></i> Restrict';
                            button.className =
                                `btn-action ${newStatus === 'restricted' ? 'btn-unrestrict' : 'btn-restrict'}`;

                            const statusCell = row.querySelector('td:nth-child(4)');
                            const oldTimer = statusCell.querySelector('.auto-restore-timer');
                            if (oldTimer) oldTimer.remove();

                            const statusBadge = row.querySelector('.status-badge');
                            if (newStatus === 'restricted') {
                                statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Restricted';
                                statusBadge.className = 'status-badge status-restricted';

                                if (data.auto_restore_at) {
                                    const timer = document.createElement('span');
                                    timer.className = 'auto-restore-timer';
                                    timer.setAttribute('data-restore-time', data.auto_restore_at);
                                    timer.innerHTML =
                                        '<i class="fas fa-clock"></i><span class="timer-text">Calculating...</span>';
                                    statusCell.appendChild(timer);
                                    updateTimers();
                                }
                            } else {
                                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                                statusBadge.className = 'status-badge status-active';
                            }

                            row.setAttribute('data-status', newStatus);
                            row.setAttribute('data-auto-restore', data.auto_restore_at || '');
                            updateStats();
                        } else {
                            showAlert(data.message || 'Operation failed', 'error');
                            button.innerHTML = originalText;
                        }
                        button.disabled = false;
                        activeRequests--;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const message = error.message === 'Failed to fetch' ?
                            'Network error. Please check your connection.' :
                            'An error occurred. Please try again.';
                        showAlert(message, 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                        activeRequests--;
                    });
            }

            function toggleActivation(button, customerId) {
                const isActive = button.textContent.includes('Deactivate');
                const action = isActive ? 'deactivate' : 'activate';

                const confirmed = confirm(
                    `Are you sure you want to ${action} this customer?${isActive ? '\n\nThe customer will be automatically restored to active status after 7 days.' : ''}`
                );
                if (!confirmed) return;

                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                activeRequests++;

                fetch(`/admin/customers/${customerId}/toggle-activation`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message || 'Status updated successfully', 'success');

                            const newStatus = data.status;
                            const row = button.closest('tr');

                            button.innerHTML = newStatus === 'active' ?
                                '<i class="fas fa-pause"></i> Deactivate' :
                                '<i class="fas fa-play"></i> Activate';
                            button.className = `btn-action ${newStatus === 'active' ? 'btn-deactivate' : 'btn-activate'}`;

                            const statusCell = row.querySelector('td:nth-child(4)');
                            const oldTimer = statusCell.querySelector('.auto-restore-timer');
                            if (oldTimer) oldTimer.remove();

                            const statusBadge = row.querySelector('.status-badge');
                            if (newStatus === 'active') {
                                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                                statusBadge.className = 'status-badge status-active';
                            } else {
                                statusBadge.innerHTML = '<i class="fas fa-pause-circle"></i> Deactivated';
                                statusBadge.className = 'status-badge status-inactive';

                                if (data.auto_restore_at) {
                                    const timer = document.createElement('span');
                                    timer.className = 'auto-restore-timer';
                                    timer.setAttribute('data-restore-time', data.auto_restore_at);
                                    timer.innerHTML =
                                        '<i class="fas fa-clock"></i><span class="timer-text">Calculating...</span>';
                                    statusCell.appendChild(timer);
                                    updateTimers();
                                }
                            }

                            row.setAttribute('data-status', newStatus);
                            row.setAttribute('data-auto-restore', data.auto_restore_at || '');
                            updateStats();
                        } else {
                            showAlert(data.message || 'Operation failed', 'error');
                            button.innerHTML = originalText;
                        }
                        button.disabled = false;
                        activeRequests--;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const message = error.message === 'Failed to fetch' ?
                            'Network error. Please check your connection.' :
                            'An error occurred. Please try again.';
                        showAlert(message, 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                        activeRequests--;
                    });
            }

            function deleteAccount(customerId) {
                const confirmed = confirm(
                    'Are you sure you want to delete this customer?\n\nThis action cannot be undone. Deleted customers can only be manually restored by an admin.'
                );
                if (!confirmed) return;

                if (!canMakeRequest()) {
                    showAlert('Too many requests. Please wait a moment.', 'error');
                    return;
                }

                showLoading();
                activeRequests++;

                fetch(`/admin/customers/${customerId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        hideLoading();
                        activeRequests--;
                        if (data.success) {
                            showAlert(data.message || 'Customer deleted successfully', 'success');

                            const row = document.querySelector(`tr[data-customer-id="${customerId}"]`);
                            if (row) {
                                row.style.opacity = '0';
                                row.style.transition = 'opacity 0.3s';
                                setTimeout(() => {
                                    row.remove();
                                    updateStats();

                                    const remainingRows = document.querySelectorAll('tbody tr[data-customer-id]');
                                    if (remainingRows.length === 0) {
                                        const tbody = document.querySelector('#customers-table tbody');
                                        tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-users-slash"></i>
                            </div>
                            <div class="empty-title">No customers found</div>
                            <div class="empty-subtitle">No customers are registered yet</div>
                        </td>
                    </tr>
                `;
                                    }
                                }, 300);
                            }
                        } else {
                            showAlert(data.message || 'Delete operation failed', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideLoading();
                        activeRequests--;
                        const message = error.message === 'Failed to fetch' ?
                            'Network error. Please check your connection.' :
                            'An error occurred. Please try again.';
                        showAlert(message, 'error');
                    });
            }

            let searchTimeout;
            document.getElementById('search-input').addEventListener('input', function(e) {
                const query = e.target.value.trim().toLowerCase();

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const rows = document.querySelectorAll('tbody tr[data-customer-id]');
                    let visibleCount = 0;

                    const existingEmpty = document.querySelector('.search-empty');
                    if (existingEmpty) existingEmpty.remove();

                    rows.forEach(row => {
                        const email = row.getAttribute('data-customer-email')?.toLowerCase() || '';
                        const phone = row.getAttribute('data-customer-phone')?.toLowerCase() || '';
                        const customerId = row.getAttribute('data-customer-number')?.toLowerCase() ||
                            '';
                        const text = row.textContent.toLowerCase();

                        if (query === '' || email.includes(query) || phone.includes(query) || customerId
                            .includes(
                                query) || text.includes(query)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    updateStats();

                    if (visibleCount === 0 && query !== '') {
                        const tbody = document.querySelector('#customers-table tbody');
                        const emptyRow = document.createElement('tr');
                        emptyRow.className = 'search-empty';
                        emptyRow.innerHTML = `
            <td colspan="5" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="empty-title">No results found</div>
                <div class="empty-subtitle">No customers match "${sanitizeText(query)}"</div>
            </td>
        `;
                        tbody.appendChild(emptyRow);
                    }
                }, 300);
            });

            document.getElementById('search-input').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            updateTimers();
            setInterval(updateTimers, 60000);

            console.log('Customer Management page initialized');
        </script>
        @stack('scripts')
    </body>

    </html>
