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
            <div class="activity-box {{ $activities['expiring_products'] > 0 ? 'warning' : '' }}" id="expiringProducts">
                ⏰ <strong>{{ $activities['expiring_products'] }}</strong> 
                product{{ $activities['expiring_products'] != 1 ? 's' : '' }} expiring within 30 days
            </div>
            
            <div class="activity-box {{ $activities['pending_prescriptions'] > 0 ? 'info' : '' }}" id="pendingPrescriptions">
                📝 <strong>{{ $activities['pending_prescriptions'] }}</strong> 
                prescription{{ $activities['pending_prescriptions'] != 1 ? 's' : '' }} awaiting confirmation
            </div>
            
            @if($activities['low_stock_products'] > 0)
            <div class="activity-box warning" id="lowStockProducts">
                📦 <strong>{{ $activities['low_stock_products'] }}</strong> 
                product{{ $activities['low_stock_products'] != 1 ? 's' : '' }} running low on stock
            </div>
            @endif
            
            @if($activities['out_of_stock_products'] > 0)
            <div class="activity-box danger" id="outOfStockProducts">
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
        
        activitiesHtml += `
            <div class="activity-box ${activities.expiring_products > 0 ? 'warning' : ''}" id="expiringProducts">
                ⏰ <strong>${activities.expiring_products}</strong> 
                product${activities.expiring_products != 1 ? 's' : ''} expiring within 30 days
            </div>
        `;
        
        activitiesHtml += `
            <div class="activity-box ${activities.pending_prescriptions > 0 ? 'info' : ''}" id="pendingPrescriptions">
                📝 <strong>${activities.pending_prescriptions}</strong> 
                prescription${activities.pending_prescriptions != 1 ? 's' : ''} awaiting confirmation
            </div>
        `;
        
        if (activities.low_stock_products > 0) {
            activitiesHtml += `
                <div class="activity-box warning" id="lowStockProducts">
                    📦 <strong>${activities.low_stock_products}</strong> 
                    product${activities.low_stock_products != 1 ? 's' : ''} running low on stock
                </div>
            `;
        }
        
        if (activities.out_of_stock_products > 0) {
            activitiesHtml += `
                <div class="activity-box danger" id="outOfStockProducts">
                    ⚠️ <strong>${activities.out_of_stock_products}</strong> 
                    product${activities.out_of_stock_products != 1 ? 's' : ''} out of stock
                </div>
            `;
        }

        if (activities.new_orders > 0) {
            activitiesHtml += `
                <div class="activity-box info" id="newOrders">
                    🆕 <strong>${activities.new_orders}</strong> 
                    new order${activities.new_orders != 1 ? 's' : ''} today
                </div>
            `;
        }

        if (activities.completed_orders_today > 0) {
            activitiesHtml += `
                <div class="activity-box success" id="completedOrders">
                    ✅ <strong>${activities.completed_orders_today}</strong> 
                    order${activities.completed_orders_today != 1 ? 's' : ''} completed today
                </div>
            `;
        }

        if (activities.today_revenue > 0) {
            activitiesHtml += `
                <div class="activity-box success" id="todayRevenue">
                    💰 Today's Revenue: <strong>₱${Number(activities.today_revenue).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
                </div>
            `;
        }

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
</script>
</body>
</html>