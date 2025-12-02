<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Footer - MJ's Pharmacy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Demo content spacer */
        .demo-content {
            min-height: 60vh;
            padding: 40px 20px;
            text-align: center;
            background: linear-gradient(to bottom, #f8f9fa, #ffffff);
        }

        .customer-footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            padding: 50px 20px 20px;
            margin-top: 40px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(236, 240, 241, 0.2);
        }

        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #3498db;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-about p {
            line-height: 1.8;
            color: #bdc3c7;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .pharmacy-hours {
            background: rgba(52, 152, 219, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 3px solid #3498db;
        }

        .pharmacy-hours p {
            margin: 8px 0;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
        }

        .pharmacy-hours strong {
            color: #3498db;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .footer-links a:hover {
            color: #3498db;
            padding-left: 8px;
        }

        .contact-item {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .contact-item:hover {
            background: rgba(52, 152, 219, 0.1);
        }

        .contact-icon {
            font-size: 20px;
            color: #3498db;
            margin-top: 2px;
        }

        .contact-details {
            flex: 1;
        }

        .contact-details strong {
            display: block;
            color: #ecf0f1;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .contact-details span {
            color: #bdc3c7;
            font-size: 13px;
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #3498db;
            transform: translateY(-3px);
        }

        .newsletter {
            background: rgba(52, 152, 219, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .newsletter p {
            margin-bottom: 15px;
            color: #bdc3c7;
            font-size: 14px;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
        }

        .newsletter-form input {
            flex: 1;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: #ecf0f1;
            font-size: 14px;
        }

        .newsletter-form input::placeholder {
            color: #95a5a6;
        }

        .newsletter-form button {
            padding: 12px 24px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
            font-size: 14px;
        }

        .newsletter-form button:hover {
            background: #2980b9;
        }

        .footer-bottom {
            padding-top: 30px;
            text-align: center;
        }

        .footer-bottom-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .footer-bottom-links a {
            color: #bdc3c7;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: #3498db;
        }

        .footer-copyright {
            color: #95a5a6;
            font-size: 13px;
            padding-top: 20px;
            border-top: 1px solid rgba(236, 240, 241, 0.1);
        }

        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .trust-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            color: #ecf0f1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 768px) {
            .footer-top {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .footer-bottom-links {
                flex-direction: column;
                gap: 15px;
            }

            .social-links {
                justify-content: center;
            }

            .trust-badges {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <footer class="customer-footer">
        <div class="footer-container">
            <div class="footer-top">
                <!-- About Section -->
                <div class="footer-column footer-about">
                    <h3>💊 MJ's Pharmacy</h3>
                    <p>Your trusted community pharmacy providing quality healthcare products and professional pharmaceutical services since 2020.</p>
                    <div class="pharmacy-hours">
                        <p><strong>📅 Opening Hours</strong></p>
                        <p><span>Monday - Saturday</span> <span>7:00 AM - 9:00 PM</span></p>
                        <p><span>Sunday</span> <span>9:00 AM - 6:00 PM</span></p>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">📘</a>
                        <a href="#" class="social-link" title="Instagram">📷</a>
                        <a href="#" class="social-link" title="Twitter">🐦</a>
                        <a href="#" class="social-link" title="Email">✉️</a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-column footer-links">
                    <h3>🔗 Quick Links</h3>
                    <ul>
                        <li><a href="/home">🏠 Home</a></li>
                        <li><a href="/home/products">🛒 Shop Products</a></li>
                        <li><a href="/home/uploads">📋 Upload Prescription</a></li>
                        <li><a href="/profile">👤 My Account</a></li>
                        <li><a href="/home/messages">💬 Messages</a></li>
                        <li><a href="/home/notifications">🔔 Notifications</a></li>
                    </ul>
                </div>

                <!-- Customer Support -->
                <div class="footer-column footer-links">
                    <h3>💡 Customer Support</h3>
                    <ul>
                        <li><a href="#">❓ FAQs</a></li>
                        <li><a href="#">📦 Order Tracking</a></li>
                        <li><a href="#">🔄 Returns & Refunds</a></li>
                        <li><a href="/home/contact-us">📞 Contact Us</a></li>
                        <li><a href="/feedback">⭐ Send Feedback</a></li>
                    </ul>
                </div>

                <!-- Contact Information -->
                <div class="footer-column">
                    <h3>📍 Get In Touch</h3>
                    <div class="contact-item">
                        <span class="contact-icon">📍</span>
                        <div class="contact-details">
                            <strong>Location</strong>
                            <span>Malabor, Tibiao Antique<br>Philippines</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <div class="contact-details">
                            <strong>Phone</strong>
                            <span>+63 956-746-0163</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">✉️</span>
                        <div class="contact-details">
                            <strong>Email</strong>
                            <span>info@mjspharmacy.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trust Badges -->
            <div class="trust-badges">
                <span class="trust-badge">✅ FDA Licensed</span>
                <span class="trust-badge">🔒 Secure Payment</span>
                <span class="trust-badge">⚕️ Licensed Pharmacists</span>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms & Conditions</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Disclaimer</a>
                    <a href="#">Accessibility</a>
                </div>
                <div class="footer-copyright">
                    <p>&copy; 2025 MJ's Pharmacy. All rights reserved. | Professional pharmaceutical care you can trust.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
