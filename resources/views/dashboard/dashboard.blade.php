<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MJ's Pharmacy</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
@include('admin.admin-header')
<main>
    <section class="left-panel">
        <div class="card">
            <div class="icon">📈</div>
            <p>Total Sales</p>
            <h2 id="totalSales">₱{{ number_format($stats['total_sales'], 2) }}</h2>
        </div>
        <div class="card">
            <div class="icon">🏢</div>
            <p>Total Products</p>
            <h2 id="totalProducts">{{ number_format($stats['total_products']) }}</h2>
        </div>
        <div class="card">
            <div class="icon">💵</div>
            <p>Total Profit</p>
            <h2 id="totalProfit">₱{{ number_format($stats['total_profit'], 2) }}</h2>
        </div>
    </section>

    <section class="right-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2>Real-time Activities</h2>
            <div id="connectionStatus" style="font-size: 12px; display: flex; align-items: center;">
                <span class="status-indicator" id="statusDot" style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; margin-right: 5px;"></span>
                <span id="statusText">Live</span>
            </div>
        </div>

        <div class="activities" id="activitiesContainer">
            <div class="activity-box {{ $activities['expiring_products'] > 0 ? 'warning' : '' }} clickable"
                 id="expiringProducts"
                 onclick="openActivityModal('expiring', 'Expiring Products', {{ $activities['expiring_products'] }})">
                ⏰ <strong>{{ $activities['expiring_products'] }}</strong>
                product{{ $activities['expiring_products'] != 1 ? 's' : '' }} expiring within 30 days
            </div>

            <div class="activity-box {{ $activities['pending_prescriptions'] > 0 ? 'info' : '' }}"
                 id="pendingPrescriptions">
                📝 <strong>{{ $activities['pending_prescriptions'] }}</strong>
                prescription{{ $activities['pending_prescriptions'] != 1 ? 's' : '' }} awaiting confirmation
            </div>

            @if($activities['low_stock_products'] > 0)
            <div class="activity-box warning clickable"
                 id="lowStockProducts"
                 onclick="openActivityModal('low_stock', 'Low Stock Products', {{ $activities['low_stock_products'] }})">
                📦 <strong>{{ $activities['low_stock_products'] }}</strong>
                product{{ $activities['low_stock_products'] != 1 ? 's' : '' }} running low on stock
            </div>
            @endif

            @if($activities['out_of_stock_products'] > 0)
            <div class="activity-box danger clickable"
                 id="outOfStockProducts"
                 onclick="openActivityModal('out_of_stock', 'Out of Stock Products', {{ $activities['out_of_stock_products'] }})">
                ⚠️ <strong>{{ $activities['out_of_stock_products'] }}</strong>
                product{{ $activities['out_of_stock_products'] != 1 ? 's' : '' }} out of stock
            </div>
            @endif

            @if($activities['new_orders'] > 0)
            <div class="activity-box info" id="newOrders">
                🆕 <strong>{{ $activities['new_orders'] }}</strong>
                new order{{ $activities['new_orders'] != 1 ? 's' : '' }} today
            </div>
            @endif

            @if($activities['completed_orders_today'] > 0)
            <div class="activity-box success" id="completedOrders">
                ✅ <strong>{{ $activities['completed_orders_today'] }}</strong>
                order{{ $activities['completed_orders_today'] != 1 ? 's' : '' }} completed today
            </div>
            @endif

            @if($activities['today_revenue'] > 0)
            <div class="activity-box success" id="todayRevenue">
                💰 Today's Revenue: <strong>₱{{ number_format($activities['today_revenue'], 2) }}</strong>
            </div>
            @endif
        </div>

        <h2 style="margin-top: 30px;">Sales Overview (Last 5 Months)</h2>
        <canvas id="salesChart" height="120"></canvas>

        <div style="text-align: center; margin-top: 20px;">
            <button id="refreshStats" class="btn btn-primary" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                🔄 Refresh Data
            </button>
            <small style="display: block; margin-top: 8px; color: #666;">
                Last updated: <span id="lastUpdated">{{ now()->format('M d, Y h:i A') }}</span>
            </small>
        </div>
    </section>
</main>

<div id="logoutModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Logout Notice</h2>
        <p>You are about to log out of your session. Do you want to proceed?</p>
        <div class="modal-buttons">
            <form action="{{ url('/admin/login') }}" method="GET">
                <button type="submit" class="btn btn-confirm">Logout</button>
            </form>
            <button class="btn btn-cancel" onclick="hideLogoutModal()">Cancel</button>
        </div>
    </div>
</div>

<div id="activityModal" class="modal-overlay">
    <div class="activity-modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Activity Details</h2>
            <button class="close-btn" onclick="closeActivityModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalLoadingState" class="loading-state">
                <div class="spinner"></div>
                <p>Loading product details...</p>
            </div>
            <div id="modalContent" style="display: none;">
                <div class="activity-summary">
                    <div class="activity-icon" id="modalIcon"></div>
                    <div class="activity-text">
                        <h3 id="modalActivityTitle"></h3>
                        <p id="modalActivityDescription"></p>
                    </div>
                </div>
                <div class="products-list" id="productsList">
                </div>
            </div>
            <div id="modalErrorState" class="error-state" style="display: none;">
                <p>❌ Failed to load product details. Please try again.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-cancel" onclick="closeActivityModal()">Cancel</button>
            <button class="btn btn-primary" id="goToProductsBtn" onclick="goToProducts()">
                Go to Products Page
            </button>
        </div>
    </div>
</div>

@stack('scripts')

<script type="text/javascript">
    window.salesChartData = JSON.parse('{!! addslashes(json_encode($salesChartData)) !!}');
</script>

<script>
    let salesChart;
    let refreshInterval;
    let connectionRetries = 0;
    const maxRetries = 3;
    const refreshIntervalTime = 30000;
    let currentActivityType = null;

    document.addEventListener('DOMContentLoaded', function() {
        initializeSalesChart(window.salesChartData);
        setupRefreshFunctionality();
        startAutoRefresh();
    });

    function setupRefreshFunctionality() {
        document.getElementById('refreshStats').addEventListener('click', function() {
            clearInterval(refreshInterval);
            refreshDashboard();
            startAutoRefresh();
        });
    }

    function startAutoRefresh() {
        refreshInterval = setInterval(refreshDashboard, refreshIntervalTime);
    }

    function initializeSalesChart(chartData) {
        const ctx = document.getElementById('salesChart').getContext('2d');

        if (salesChart) {
            salesChart.destroy();
        }

        salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Sales (₱)',
                    data: chartData.data,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 500,
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    function refreshDashboard() {
        const refreshBtn = document.getElementById('refreshStats');
        const originalText = refreshBtn.innerHTML;

        refreshBtn.innerHTML = '🔄 Refreshing...';
        refreshBtn.disabled = true;
        updateConnectionStatus('refreshing', 'Updating...');

        fetch('{{ route("admin.dashboard.stats") }}', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            updateStatsCards(data.stats);
            updateActivities(data.activities);
            initializeSalesChart(data.salesChart);
            updateLastUpdated();
            updateConnectionStatus('connected', 'Live');
            connectionRetries = 0;

            refreshBtn.innerHTML = originalText;
            refreshBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
            connectionRetries++;

            if (connectionRetries < maxRetries) {
                updateConnectionStatus('warning', `Retrying... (${connectionRetries}/${maxRetries})`);
                setTimeout(refreshDashboard, 5000);
            } else {
                updateConnectionStatus('error', 'Connection failed');
                refreshBtn.innerHTML = '❌ Retry';
            }

            refreshBtn.disabled = false;
        });
    }

    function updateStatsCards(stats) {
        document.getElementById('totalSales').textContent = '₱' + Number(stats.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('totalProducts').textContent = Number(stats.total_products).toLocaleString();
        document.getElementById('totalProfit').textContent = '₱' + Number(stats.total_profit).toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    function updateActivities(activities) {
        const container = document.getElementById('activitiesContainer');
        let activitiesHtml = '';

        // Expiring products - clickable
        activitiesHtml += `
            <div class="activity-box ${activities.expiring_products > 0 ? 'warning clickable' : ''}" id="expiringProducts"
                 ${activities.expiring_products > 0 ? `onclick="openActivityModal('expiring', 'Expiring Products', ${activities.expiring_products})"` : ''}>
                ⏰ <strong>${activities.expiring_products}</strong>
                product${activities.expiring_products != 1 ? 's' : ''} expiring within 30 days
            </div>
        `;

        // Pending prescriptions - NOT clickable
        activitiesHtml += `
            <div class="activity-box ${activities.pending_prescriptions > 0 ? 'info' : ''}" id="pendingPrescriptions">
                📝 <strong>${activities.pending_prescriptions}</strong>
                prescription${activities.pending_prescriptions != 1 ? 's' : ''} awaiting confirmation
            </div>
        `;

        // Low stock products - clickable
        if (activities.low_stock_products > 0) {
            activitiesHtml += `
                <div class="activity-box warning clickable" id="lowStockProducts"
                     onclick="openActivityModal('low_stock', 'Low Stock Products', ${activities.low_stock_products})">
                    📦 <strong>${activities.low_stock_products}</strong>
                    product${activities.low_stock_products != 1 ? 's' : ''} running low on stock
                </div>
            `;
        }

        // Out of stock products - clickable
        if (activities.out_of_stock_products > 0) {
            activitiesHtml += `
                <div class="activity-box danger clickable" id="outOfStockProducts"
                     onclick="openActivityModal('out_of_stock', 'Out of Stock Products', ${activities.out_of_stock_products})">
                    ⚠️ <strong>${activities.out_of_stock_products}</strong>
                    product${activities.out_of_stock_products != 1 ? 's' : ''} out of stock
                </div>
            `;
        }

        // New orders - NOT clickable
        if (activities.new_orders > 0) {
            activitiesHtml += `
                <div class="activity-box info" id="newOrders">
                    🆕 <strong>${activities.new_orders}</strong>
                    new order${activities.new_orders != 1 ? 's' : ''} today
                </div>
            `;
        }

        // Completed orders - NOT clickable
        if (activities.completed_orders_today > 0) {
            activitiesHtml += `
                <div class="activity-box success" id="completedOrders">
                    ✅ <strong>${activities.completed_orders_today}</strong>
                    order${activities.completed_orders_today != 1 ? 's' : ''} completed today
                </div>
            `;
        }

        // Today's revenue - NOT clickable
        if (activities.today_revenue > 0) {
            activitiesHtml += `
                <div class="activity-box success" id="todayRevenue">
                    💰 Today's Revenue: <strong>₱${Number(activities.today_revenue).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
                </div>
            `;
        }

        // Approved orders - NOT clickable
        if (activities.approved_orders_today > 0) {
            activitiesHtml += `
                <div class="activity-box success" id="approvedOrders">
                    ✅ <strong>${activities.approved_orders_today}</strong>
                    order${activities.approved_orders_today != 1 ? 's' : ''} approved today
                </div>
            `;
        }

        container.innerHTML = activitiesHtml;
    }

    function openActivityModal(activityType, title, count) {
        currentActivityType = activityType;
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalActivityTitle').textContent = title;
        document.getElementById('modalActivityDescription').textContent = `${count} item${count != 1 ? 's' : ''} requiring attention`;

        const iconMap = {
            'expiring': '⏰',
            'low_stock': '📦',
            'out_of_stock': '⚠️'
        };

        document.getElementById('modalIcon').textContent = iconMap[activityType] || '📋';

        document.getElementById('activityModal').classList.add('active');
        document.getElementById('modalLoadingState').style.display = 'flex';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalErrorState').style.display = 'none';

        fetchActivityDetails(activityType);
    }

    function fetchActivityDetails(activityType) {
        let endpoint = '';

        switch(activityType) {
            case 'expiring':
                endpoint = '{{ route("admin.dashboard.expiring-products") }}';
                break;
            case 'low_stock':
                endpoint = '{{ route("admin.dashboard.low-stock-products") }}';
                break;
            case 'out_of_stock':
                endpoint = '{{ route("admin.dashboard.out-of-stock-products") }}';
                break;
            default:
                showModalError();
                return;
        }

        fetch(endpoint, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            displayActivityDetails(data, activityType);
        })
        .catch(error => {
            console.error('Error fetching activity details:', error);
            showModalError();
        });
    }

    function displayActivityDetails(data, activityType) {
        const productsList = document.getElementById('productsList');
        let productsHtml = '';

        if (data.length === 0) {
            productsHtml = '<p class="no-products">No products found.</p>';
        } else {
            data.forEach(product => {
                const productCard = createProductCard(product, activityType);
                productsHtml += productCard;
            });
        }

        productsList.innerHTML = productsHtml;

        document.getElementById('modalLoadingState').style.display = 'none';
        document.getElementById('modalContent').style.display = 'block';
    }

    function createProductCard(product, activityType) {
        let detailsHtml = '';

        switch(activityType) {
            case 'expiring':
                const expiryDate = new Date(product.expiration_date);
                const daysUntilExpiry = Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
                detailsHtml = `
                    <div class="product-detail"><strong>Batch:</strong> ${product.batch_number}</div>
                    <div class="product-detail"><strong>Expires:</strong> ${expiryDate.toLocaleDateString()} (${daysUntilExpiry} days)</div>
                    <div class="product-detail"><strong>Quantity:</strong> ${product.quantity_remaining}</div>
                `;
                break;
            case 'low_stock':
            case 'out_of_stock':
                detailsHtml = `
                    <div class="product-detail"><strong>Current Stock:</strong> ${product.current_stock || 0}</div>
                    <div class="product-detail"><strong>Reorder Level:</strong> ${product.reorder_level || 'Not set'}</div>
                `;
                break;
        }

        return `
            <div class="product-card">
                <div class="product-header">
                    <h4>${product.product_name}</h4>
                    <span class="product-code">${product.product_code}</span>
                </div>
                <div class="product-details">
                    ${detailsHtml}
                </div>
            </div>
        `;
    }

    function showModalError() {
        document.getElementById('modalLoadingState').style.display = 'none';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalErrorState').style.display = 'block';
    }

    function closeActivityModal() {
        document.getElementById('activityModal').classList.remove('active');
        currentActivityType = null;
    }

    function goToProducts() {
        let url = '{{ route("products.index") }}';

        if (currentActivityType === 'expiring') {
            url += '?filter=expiring';
        } else if (currentActivityType === 'low_stock') {
            url += '?filter=low_stock';
        } else if (currentActivityType === 'out_of_stock') {
            url += '?filter=out_of_stock';
        }

        window.location.href = url;
    }

    function updateConnectionStatus(status, text) {
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');

        statusText.textContent = text;

        switch(status) {
            case 'connected':
                statusDot.style.background = '#22c55e';
                break;
            case 'refreshing':
                statusDot.style.background = '#3b82f6';
                break;
            case 'warning':
                statusDot.style.background = '#f59e0b';
                break;
            case 'error':
                statusDot.style.background = '#ef4444';
                break;
        }
    }

    function updateLastUpdated() {
        const now = new Date();
        const options = {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        };
        document.getElementById('lastUpdated').textContent = now.toLocaleDateString('en-US', options);
    }

    function showLogoutModal() {
        document.getElementById("logoutModal").classList.add("active");
    }

    function hideLogoutModal() {
        document.getElementById("logoutModal").classList.remove("active");
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else {
            refreshDashboard();
            startAutoRefresh();
        }
    });

    window.addEventListener('beforeunload', function() {
        clearInterval(refreshInterval);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeActivityModal();
            hideLogoutModal();
        }
    });
</script>
</body>
</html>
