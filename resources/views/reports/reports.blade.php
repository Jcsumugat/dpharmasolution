<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

    @include('admin.admin-header')

    <main>
        <section class="left-panel">
            <div class="card report-form">
                <h2>Generate Sales Report</h2>
                <hr><br>

                <form id="reportForm">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" required>

                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" required>

                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report_type" style="padding: 10px; width: 100%; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="sales">Sales Report</option>
                        <option value="inventory">Inventory Report</option>
                        <option value="products">Product Report</option>
                    </select>

                    <!-- New Sales Source Filter -->
                    <div id="sales_source_container" style="display: block;">
                        <label for="sales_source">Sales Source</label>
                        <select id="sales_source" name="sales_source" style="padding: 10px; width: 100%; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="all">All Sales (Online + Walk-in)</option>
                            <option value="online">Online Orders Only</option>
                            <option value="walkin">Walk-in Sales Only</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-generate">
                        <span class="btn-text">Generate Report</span>
                        <span class="btn-loading" style="display: none;">Generating...</span>
                    </button>
                </form>
            </div>
        </section>

        <section class="right-panel report-section">
            <div class="report-box">
                <!-- Print Header (hidden by default, shown only during print) -->
                <div class="print-header" style="display: none;">
                    <h1>MJ's Pharmacy</h1>
                    <h2 id="print-report-title">Sales Report</h2>
                    <p><strong>Date Range:</strong> <span id="print-date-range"></span></p>
                    <p><strong>Sales Source:</strong> <span id="print-sales-source"></span></p>
                    <p><strong>Generated On:</strong> {{ \Carbon\Carbon::now()->format('F j, Y g:i A') }}</p>
                </div>

                <!-- Action Buttons -->
                <div class="btn-container" id="actionButtons" style="display: none;">
                    <button type="button" class="btn-print" id="printBtn" onclick="printReport()">
                        Print Report
                    </button>
                    <button type="button" class="btn-export" id="exportPdfBtn" onclick="exportToPDF()">
                        Export PDF
                    </button>
                    <button type="button" class="btn-export" id="exportExcelBtn" onclick="exportToExcel()">
                        Export Excel
                    </button>
                </div>

                <h2>Sales Report — MJ's Pharmacy</h2>
                <p><strong>Date Range:</strong> <span id="reportRange">Please select date range and generate report</span></p>
                <p><strong>Sales Source:</strong> <span id="reportSource">All Sales</span></p>
                <p><strong>Generated On:</strong> {{ \Carbon\Carbon::now()->format('F j, Y g:i A') }}</p>

                <div id="reportContent">
                    <div class="alert alert-info">
                        <strong>Ready to Generate Report</strong><br>
                        Please select your date range and click "Generate Report" to view the sales data.
                    </div>
                </div>

                <!-- Print Footer (hidden by default, shown only during print) -->
                <div class="print-footer" style="display: none;">
                    <p>This report was generated automatically by MJ's Pharmacy Management System</p>
                    <p>For questions or concerns, please contact the system administrator</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Logout Notice</h2>
            <p>You are about to log out of your session. Do you want to proceed?</p>
            <div class="modal-buttons">
                <form action="{{ url('/login') }}" method="GET">
                    <button type="submit" class="btn btn-confirm">Logout</button>
                </form>
                <button class="btn btn-cancel" onclick="hideLogoutModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Global variables to store current report data
        let currentReportData = null;
        let currentReportType = null;
        let currentDateRange = null;
        let currentSalesSource = null;

        // Set CSRF token for AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Set max date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').setAttribute('max', today);
            document.getElementById('end_date').setAttribute('max', today);

            // Set default dates (last 30 days)
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            document.getElementById('start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
            document.getElementById('end_date').value = today;

            // Show/hide sales source filter based on report type
            document.getElementById('report_type').addEventListener('change', function() {
                const salesSourceContainer = document.getElementById('sales_source_container');
                if (this.value === 'sales') {
                    salesSourceContainer.style.display = 'block';
                } else {
                    salesSourceContainer.style.display = 'none';
                }
            });
        });

        function showLogoutModal() {
            document.getElementById("logoutModal").classList.add("active");
        }

        function hideLogoutModal() {
            document.getElementById("logoutModal").classList.remove("active");
        }

        // Handle form submission
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateReport();
        });

        // Date validation
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');

            if (startDate) {
                endDateInput.setAttribute('min', startDate);
                if (endDateInput.value && endDateInput.value < startDate) {
                    endDateInput.value = startDate;
                }
            }
        });

        function generateReport() {
            const startDate = document.getElementById("start_date").value;
            const endDate = document.getElementById("end_date").value;
            const reportType = document.getElementById("report_type").value;
            const salesSource = document.getElementById("sales_source").value;

            if (!startDate || !endDate) {
                alert("Please select both start and end dates.");
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert("Start date cannot be after end date.");
                return;
            }

            // Show loading state
            const btnText = document.querySelector('.btn-text');
            const btnLoading = document.querySelector('.btn-loading');
            const generateBtn = document.querySelector('.btn-generate');

            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            generateBtn.disabled = true;

            // Update date range display
            const rangeText = new Date(startDate).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) + " – " + new Date(endDate).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById("reportRange").innerText = rangeText;

            // Update sales source display
            const sourceText = getSalesSourceText(salesSource);
            document.getElementById("reportSource").innerText = sourceText;

            // Store current report parameters
            currentReportType = reportType;
            currentDateRange = rangeText;
            currentSalesSource = salesSource;

            // Make AJAX request to generate report
            fetch('/admin/reports/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        start_date: startDate,
                        end_date: endDate,
                        report_type: reportType,
                        sales_source: salesSource
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentReportData = data.data;
                        displayReport(data.data, reportType, salesSource);
                        showActionButtons();
                    } else {
                        showError(data.message || 'Error generating report');
                        hideActionButtons();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error occurred. Please try again.');
                    hideActionButtons();
                })
                .finally(() => {
                    // Reset button state
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    generateBtn.disabled = false;
                });
        }

        function getSalesSourceText(source) {
            switch(source) {
                case 'online': return 'Online Orders Only';
                case 'walkin': return 'Walk-in Sales Only';
                case 'all':
                default: return 'All Sales (Online + Walk-in)';
            }
        }

        function showActionButtons() {
            document.getElementById('actionButtons').style.display = 'block';
        }

        function hideActionButtons() {
            document.getElementById('actionButtons').style.display = 'none';
        }

        function printReport() {
            if (!currentReportData || !currentReportType) {
                alert('Please generate a report first.');
                return;
            }

            // Update print header content
            document.getElementById('print-report-title').textContent =
                currentReportType.charAt(0).toUpperCase() + currentReportType.slice(1) + ' Report';
            document.getElementById('print-date-range').textContent = currentDateRange;
            document.getElementById('print-sales-source').textContent = getSalesSourceText(currentSalesSource);

            // Show print-specific elements
            const printElements = document.querySelectorAll('.print-header, .print-footer');
            printElements.forEach(el => el.style.display = 'block');

            // Add print-specific classes to low stock alerts
            const lowStockElements = document.querySelectorAll('.low-stock-alert');
            lowStockElements.forEach(el => el.classList.add('low-stock-print'));

            // Trigger print
            window.print();

            // Hide print-specific elements after print dialog closes
            setTimeout(() => {
                printElements.forEach(el => el.style.display = 'none');
                lowStockElements.forEach(el => el.classList.remove('low-stock-print'));
            }, 100);
        }

        function exportToPDF() {
            if (!currentReportData || !currentReportType) {
                alert('Please generate a report first.');
                return;
            }

            const startDate = document.getElementById("start_date").value;
            const endDate = document.getElementById("end_date").value;
            const salesSource = document.getElementById("sales_source").value;

            // Create form and submit for PDF export
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/reports/export-pdf';
            form.target = '_blank';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);

            // Add form data
            const fields = {
                start_date: startDate,
                end_date: endDate,
                report_type: currentReportType,
                sales_source: salesSource
            };

            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function exportToExcel() {
            if (!currentReportData || !currentReportType) {
                alert('Please generate a report first.');
                return;
            }

            const startDate = document.getElementById("start_date").value;
            const endDate = document.getElementById("end_date").value;
            const salesSource = document.getElementById("sales_source").value;

            // Create form and submit for Excel export
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/reports/export-excel';
            form.target = '_blank';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);

            // Add form data
            const fields = {
                start_date: startDate,
                end_date: endDate,
                report_type: currentReportType,
                sales_source: salesSource
            };

            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function displayReport(data, reportType, salesSource = 'all') {
            const reportContent = document.getElementById('reportContent');

            if (reportType === 'sales') {
                reportContent.innerHTML = `
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Total Sales</h4>
                        <p class="value">₱${data.summary.total_sales || '0.00'}</p>
                        ${salesSource !== 'all' ? `<small>${getSalesSourceText(salesSource)}</small>` : ''}
                    </div>
                    <div class="summary-card">
                        <h4>Total Items Sold</h4>
                        <p class="value">${data.summary.total_items || '0'}</p>
                    </div>
                    <div class="summary-card">
                        <h4>Total Transactions</h4>
                        <p class="value">${data.summary.total_transactions || '0'}</p>
                    </div>
                    <div class="summary-card">
                        <h4>Average Sale</h4>
                        <p class="value">₱${data.summary.average_sale || '0.00'}</p>
                    </div>
                </div>

                ${data.breakdown && salesSource === 'all' ? `
                <div class="sales-breakdown">
                    <h3>Sales Breakdown by Source</h3>
                    <div class="breakdown-grid">
                        <div class="breakdown-card">
                            <h4>Online Orders</h4>
                            <p class="breakdown-amount">₱${data.breakdown.online_sales || '0.00'}</p>
                            <small>${data.breakdown.online_transactions || 0} transactions</small>
                        </div>
                        <div class="breakdown-card">
                            <h4>Walk-in Sales</h4>
                            <p class="breakdown-amount">₱${data.breakdown.walkin_sales || '0.00'}</p>
                            <small>${data.breakdown.walkin_transactions || 0} transactions</small>
                        </div>
                    </div>
                </div>
                ` : ''}

                ${data.low_stock && data.low_stock.length > 0 ? `
                <div class="low-stock-alert">
                    <h3>Low Stock Alerts</h3>
                    <ul>
                        ${data.low_stock.map(item => `
                            <li>${item.name} — Only ${item.quantity_remaining || item.quantity} left</li>
                        `).join('')}
                    </ul>
                </div>
                ` : ''}

                <h3>Detailed Sales Report</h3>
                ${data.sales && data.sales.length > 0 ? `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Sold</th>
                            <th>Unit Price</th>
                            <th>Total Amount</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.sales.map(sale => `
                            <tr>
                                <td>${sale.product_name}</td>
                                <td>${sale.quantity_sold}</td>
                                <td>₱${sale.unit_price}</td>
                                <td>₱${sale.total_amount}</td>
                                <td>
                                    <span class="source-badge ${sale.source}">
                                        ${sale.source === 'online' ? 'Online' : 'Walk-in'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ` : '<p>No sales data found for the selected period and source.</p>'}
            `;
            } else if (reportType === 'inventory') {
                reportContent.innerHTML = `
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Total Products</h4>
                        <p class="value">${data.summary.total_products || '0'}</p>
                    </div>
                    <div class="summary-card">
                        <h4>Total Stock</h4>
                        <p class="value">${data.summary.total_stock || '0'}</p>
                    </div>
                    <div class="summary-card">
                        <h4>Total Value</h4>
                        <p class="value">₱${data.summary.total_value || '0.00'}</p>
                    </div>
                    <div class="summary-card">
                        <h4>Low Stock Items</h4>
                        <p class="value">${data.summary.low_stock_count || '0'}</p>
                    </div>
                </div>

                <h3>Inventory Report</h3>
                ${data.inventory && data.inventory.length > 0 ? `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Brand Name</th>
                            <th>Category</th>
                            <th>Batch Number</th>
                            <th>Stock Remaining</th>
                            <th>Sale Price</th>
                            <th>Total Value</th>
                            <th>Expiry Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.inventory.map(item => `
                            <tr>
                                <td>${item.id}</td>
                                <td>${item.product_name}</td>
                                <td>${item.brand_name}</td>
                                <td>${item.category}</td>
                                <td>${item.batch_number || 'N/A'}</td>
                                <td>${item.quantity_remaining || item.stock_remaining || '0'}</td>
                                <td>₱${item.sale_price || item.unit_price || '0.00'}</td>
                                <td>₱${item.total_value || '0.00'}</td>
                                <td>${item.expiry_date || 'N/A'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ` : '<p>No inventory data found.</p>'}
            `;
            } else if (reportType === 'products') {
                reportContent.innerHTML = `
                <h3>Product Performance Report</h3>
                ${data.products && data.products.length > 0 ? `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Brand Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Latest Price</th>
                            <th>Total Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.products.map(product => `
                            <tr>
                                <td>${product.id}</td>
                                <td>${product.product_name}</td>
                                <td>${product.brand_name}</td>
                                <td>${product.category}</td>
                                <td>${product.current_stock || product.quantity_remaining || '0'}</td>
                                <td>₱${product.latest_price || product.unit_price || '0.00'}</td>
                                <td>${product.total_sold || '0'}</td>
                                <td>₱${product.total_revenue || '0.00'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                ${data.categories && data.categories.length > 0 ? `
                <h3>Category Performance</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Product Count</th>
                            <th>Total Stock</th>
                            <th>Total Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.categories.map(category => `
                            <tr>
                                <td>${category.category}</td>
                                <td>${category.product_count}</td>
                                <td>${category.total_stock || '0'}</td>
                                <td>${category.total_sold}</td>
                                <td>₱${category.total_revenue}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ` : ''}
                ` : '<p>No product data found for the selected period.</p>'}
            `;
            }
        }

        function showError(message) {
            const reportContent = document.getElementById('reportContent');
            reportContent.innerHTML = `
            <div class="alert alert-warning">
                <strong>Error</strong><br>
                ${message}
            </div>
        `;
        }
    </script>
@stack('scripts')
</body>

</html>
