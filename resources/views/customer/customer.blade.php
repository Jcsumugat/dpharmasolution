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
                <div class="stat-number" id="restrictedCount">{{ $customers->where('status', 'restricted')->count() }}</div>
                <div class="stat-label">Restricted</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon inactive">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-number" id="inactiveCount">{{ $customers->where('status', 'deactivated')->count() }}</div>
                <div class="stat-label">Deactivated</div>
            </div>

        </div>

        <div id="alert-container"></div>

        <div class="controls-section">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Search by email or phone number...">
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
                        <th>Contact Info</th>
                        <th>Address</th>
                        <th>Registration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr data-customer-id="{{ $customer->id }}" data-status="{{ $customer->status }}">
                        <td><span class="customer-id">{{ $customer->customer_id }}</span></td>
                        <td>
                            <div class="customer-name">Customer #{{ $customer->id }}</div>
                            <div class="customer-email">{{ $customer->email_address }}</div>
                        </td>
                        <td>{{ $customer->contact_number }}</td>
                        <td>{{ $customer->address }}</td>
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
                            @break
                            @case('deactivated')
                            <span class="status-badge status-inactive">
                                <i class="fas fa-pause-circle"></i> Deactivated
                            </span>
                            @break
                            @default
                            <span class="status-badge status-inactive">
                                <i class="fas fa-question-circle"></i> {{ ucfirst($customer->status) }}
                            </span>
                            @endswitch
                        </td>
                        <td class="actions">
                            @if($customer->status === 'restricted')
                            <button class="btn-action btn-unrestrict" data-customer-id="{{ $customer->id }}" data-action="restrict">
                                <i class="fas fa-unlock"></i> Unrestrict
                            </button>
                            @else
                            <button class="btn-action btn-restrict" data-customer-id="{{ $customer->id }}" data-action="restrict">
                                <i class="fas fa-lock"></i> Restrict
                            </button>
                            @endif

                            @if($customer->status === 'active')
                            <button class="btn-action btn-deactivate" data-customer-id="{{ $customer->id }}" data-action="toggle-activation">
                                <i class="fas fa-pause"></i> Deactivate
                            </button>
                            @elseif($customer->status === 'deactivated')
                            <button class="btn-action btn-activate" data-customer-id="{{ $customer->id }}" data-action="toggle-activation">
                                <i class="fas fa-play"></i> Activate
                            </button>
                            @endif

                            <button class="btn-action btn-delete" data-customer-id="{{ $customer->id }}" data-action="delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty-state">
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

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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
            document.getElementById('loadingOverlay').classList.add('show');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;

            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            alertDiv.innerHTML = `<i class="fas ${icon}"></i>${message}`;

            alertContainer.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        }

        document.addEventListener('click', function(e) {
            if (e.target.matches('button[data-action]') || e.target.closest('button[data-action]')) {
                const button = e.target.matches('button[data-action]') ? e.target : e.target.closest('button[data-action]');
                const customerId = parseInt(button.getAttribute('data-customer-id'));
                const action = button.getAttribute('data-action');

                switch (action) {
                    case 'restrict':
                        restrictAccount(customerId, button);
                        break;
                    case 'toggle-activation':
                        toggleActivation(button, customerId);
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

            const confirmed = confirm(`Are you sure you want to ${action} this customer?`);
            if (!confirmed) return;

            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch(`/admin/customers/${customerId}/restrict`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');

                        const newStatus = data.status;

                        button.innerHTML = newStatus === 'restricted' ?
                            '<i class="fas fa-unlock"></i> Unrestrict' :
                            '<i class="fas fa-lock"></i> Restrict';
                        button.className = `btn-action ${newStatus === 'restricted' ? 'btn-unrestrict' : 'btn-restrict'}`;

                        const row = button.closest('tr');
                        const statusBadge = row.querySelector('.status-badge');

                        if (newStatus === 'restricted') {
                            statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Restricted';
                            statusBadge.className = 'status-badge status-restricted';
                        } else {
                            statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                            statusBadge.className = 'status-badge status-active';
                        }

                        row.setAttribute('data-status', newStatus);
                        updateStats();
                    } else {
                        showAlert(data.message, 'error');
                        button.innerHTML = originalText;
                    }
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }

        function toggleActivation(button, customerId) {
            const isActive = button.textContent.includes('Deactivate');
            const action = isActive ? 'deactivate' : 'activate';

            const confirmed = confirm(`Are you sure you want to ${action} this customer?`);
            if (!confirmed) return;

            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch(`/admin/customers/${customerId}/toggle-activation`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');

                        const newStatus = data.status;

                        button.innerHTML = newStatus === 'active' ?
                            '<i class="fas fa-pause"></i> Deactivate' :
                            '<i class="fas fa-play"></i> Activate';
                        button.className = `btn-action ${newStatus === 'active' ? 'btn-deactivate' : 'btn-activate'}`;

                        const row = button.closest('tr');
                        const statusBadge = row.querySelector('.status-badge');

                        if (newStatus === 'active') {
                            statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                            statusBadge.className = 'status-badge status-active';
                        } else {
                            statusBadge.innerHTML = '<i class="fas fa-pause-circle"></i> Deactivated';
                            statusBadge.className = 'status-badge status-inactive';
                        }

                        row.setAttribute('data-status', newStatus);
                        updateStats();
                    } else {
                        showAlert(data.message, 'error');
                        button.innerHTML = originalText;
                    }
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }

        function deleteAccount(customerId) {
            const confirmed = confirm('Are you sure you want to delete this customer? This will mark them as deleted.');
            if (!confirmed) return;

            showLoading();

            fetch(`/admin/customers/${customerId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showAlert(data.message, 'success');

                        const row = document.querySelector(`tr[data-customer-id="${customerId}"]`);
                        if (row) {
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                updateStats();

                                const remainingRows = document.querySelectorAll('tbody tr[data-customer-id]');
                                if (remainingRows.length === 0) {
                                    const tbody = document.querySelector('#customers-table tbody');
                                    tbody.innerHTML = `
                                    <tr>
                                        <td colspan="7" class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-users-slash"></i>
                                            </div>
                                            <div class="empty-title">No customers found</div>
                                            <div class="empty-subtitle">No customers match your current search criteria</div>
                                        </td>
                                    </tr>
                                `;
                                }
                            }, 300);
                        }
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();
                    showAlert('An error occurred. Please try again.', 'error');
                });
        }

        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', function(e) {
            const query = e.target.value.trim().toLowerCase();

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const rows = document.querySelectorAll('tbody tr[data-customer-id]');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (query === '' || text.includes(query)) {
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
                        <td colspan="7" class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="empty-title">No results found</div>
                            <div class="empty-subtitle">No customers match "${query}"</div>
                        </td>
                    `;

                    const existingEmpty = tbody.querySelector('.search-empty');
                    if (existingEmpty) existingEmpty.remove();

                    tbody.appendChild(emptyRow);
                } else {
                    const existingEmpty = document.querySelector('.search-empty');
                    if (existingEmpty) existingEmpty.remove();
                }
            }, 300);
        });
    </script>
    @stack('scripts')
</body>

</html>