<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Example</title>
    <style>
        body {
            overflow-x: hidden;
        }

        .admin-footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px 20px;
            margin: 20px 0 0 0;
            box-sizing: border-box;
        }

        .footer-content {
            max-width: 1100px;
            margin: 0 auto 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 8px;
        }

        .footer-section p {
            line-height: 1.6;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }

        .license-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-top: 10px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-section a:hover {
            color: white;
            transform: translateX(5px);
        }

        .contact-info {
            display: flex;
            align-items: start;
            margin-bottom: 12px;
            gap: 10px;
            color: rgba(255, 255, 255, 0.9);
        }

        .contact-info .icon {
            font-size: 18px;
            margin-top: 2px;
        }

        .footer-bottom {
            max-width: 1100px;
            margin: 0 auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            font-size: 14px;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .footer-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-tagline {
            margin-top: 10px;
            margin-bottom: 0;
            font-size: 12px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .admin-footer {
                margin: 20px 0 0 0;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-links {
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <footer class="admin-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>MJ's Pharmacy</h3>
                <p>Your trusted partner in health and wellness. Providing quality pharmaceutical products and
                    professional healthcare services.</p>
                <div class="license-badge">FDA Licensed • LTO Certified</div>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="{{ route('dashboard') }}">📊 Dashboard</a></li>
                    <li><a href="{{ route('inventory.index') }}">📦 Inventory</a></li>
                    <li><a href="{{ route('products.index') }}">📦 Products</a></li>
                    <li><a href="{{ route('orders.index') }}">📋 Orders</a></li>
                    <li><a href="{{ route('pos.index') }}">🛒 POS System</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Management</h3>
                <ul>
                    <li><a href="{{ route('reports.index') }}">📈 Reports</a></li>
                    <li><a href="{{ route('customer.index') }}">👥 Customers</a></li>
                    <li><a href="{{ route('suppliers.index') }}">🚚 Suppliers</a></li>
                    <li><a href="{{ route('sales.index') }}">💰 Sales</a></li>
                    <li><a href="{{ route('chat.index') }}">💬 Chat Support</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact Information</h3>
                <div class="contact-info">
                    <span class="icon">📍</span>
                    <div>
                        <strong>Address:</strong><br>
                        Quezon City, Metro Manila<br>
                        Philippines
                    </div>
                </div>
                <div class="contact-info">
                    <span class="icon">📞</span>
                    <div>
                        <strong>Phone:</strong><br>
                        +63 956-746-0163
                    </div>
                </div>
                <div class="contact-info">
                    <span class="icon">⏰</span>
                    <div>
                        <strong>Hours:</strong><br>
                        Mon-Sat: 7:00 AM - 9:00 PM<br>
                        Sunday: 9:00 AM - 6:00 PM
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p class="footer-tagline">⚕️ Professional pharmaceutical care you can trust</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Data Protection</a>
                    <a href="#">Disclaimer</a>
                </div>
            </div>
            <div style="margin-top: 3rem;">&copy; 2025 MJ's Pharmacy. All rights reserved.</div>
        </div>
    </footer>
</body>

</html>
