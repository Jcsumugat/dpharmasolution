<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Home - MJ's Pharmacy</title>
   <link rel="stylesheet" href="{{ asset('css/customer/clienthome.css') }}">
</head>

<body>
@include('client.client-header')

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <h1>Welcome to MJ's Pharmacy</h1>
      <p class="subtitle">Your trusted healthcare partner providing professional pharmaceutical services, expert advice, and convenient solutions for all your health needs.</p>
      <div class="btn-group">
        <a href="/home/products" class="btn btn-primary">
          🛍️ Shop Products
        </a>
        <a href="/home/contact-us" class="btn btn-secondary">
          📞 Contact Us
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<div class="container">
  <div class="stats">
    <div class="stat-item">
      <span class="stat-number">10,000+</span>
      <span class="stat-label">Happy Customers</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">15+</span>
      <span class="stat-label">Years Experience</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">24/7</span>
      <span class="stat-label">Support Available</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">Fast</span>
      <span class="stat-label">Delivery Service</span>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container">
  <div class="main-content">
    
    <!-- Services Section -->
    <section class="services-section">
      <div class="section-header">
        <h2 class="section-title">Our Services</h2>
        <p class="section-subtitle">Comprehensive healthcare solutions designed to meet your every need with professional care and convenience.</p>
      </div>
      
      <div class="services-grid">
        <div class="service-card">
          <div class="service-icon">💊</div>
          <h3>Prescription Services</h3>
          <p>Fast, accurate prescription filling with automatic refill reminders and medication counseling from our licensed pharmacists.</p>
        </div>
        
        <div class="service-card">
          <div class="service-icon">🚚</div>
          <h3>Free Delivery</h3>
          <p>Convenient home delivery service for prescriptions and over-the-counter medications with same-day delivery options available.</p>
        </div>
        
        <div class="service-card">
          <div class="service-icon">💉</div>
          <h3>Health Services</h3>
          <p>Comprehensive health screenings, vaccinations, and wellness consultations to help you maintain optimal health.</p>
        </div>
        
        <div class="service-card">
          <div class="service-icon">💬</div>
          <h3>Expert Consultation</h3>
          <p>One-on-one consultations with our experienced pharmacists for medication management and health advice.</p>
        </div>
        
        <div class="service-card">
          <div class="service-icon">🏥</div>
          <h3>Medical Supplies</h3>
          <p>Wide selection of medical equipment, first aid supplies, and health monitoring devices for home care.</p>
        </div>
        
        <div class="service-card">
          <div class="service-icon">📱</div>
          <h3>Digital Services</h3>
          <p>Online prescription management, mobile app ordering, and digital health records for seamless healthcare.</p>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
      <div class="about-content">
        <h2>Why Choose MJ's Pharmacy?</h2>
        <p>For over 15 years, MJ's Pharmacy has been serving our community with dedication, professionalism, and genuine care. We combine traditional pharmaceutical excellence with modern convenience to provide you with the best possible healthcare experience.</p>
        <p>Our team of licensed pharmacists and healthcare professionals is committed to ensuring you receive not just the medications you need, but also the knowledge and support to use them safely and effectively.</p>
        
        <div class="features-list">
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <span>Licensed Pharmacists</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <span>Quality Medications</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <span>Competitive Prices</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <span>Insurance Accepted</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <span>Medication Counseling</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <span>Health Screenings</span>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="cta-content">
        <h2>Ready to Experience Better Healthcare?</h2>
        <p>Join thousands of satisfied customers who trust MJ's Pharmacy for their healthcare needs. Get started today!</p>
        <div class="cta-buttons">
          <a href="/home/products" class="cta-btn">
            🛒 Browse Products
          </a>
          <a href="/home/contact-us" class="cta-btn">
            📍 Visit Our Store
          </a>
        </div>
      </div>
    </section>

  </div>
</div>
@stack('scripts')
</body>
</html>