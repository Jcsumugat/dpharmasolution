<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Us - MJ's Pharmacy</title>
  <link rel="stylesheet" href="{{ asset('css/customer/contactus.css') }}" />
</head>

<body>
  @include('client.client-header')

  <!-- Contact Hero Section -->
  <section class="contact-hero">
    <div class="container">
      <div class="contact-hero-content">
        <h1>Get in Touch</h1>
        <p>We're here to help you with all your pharmacy needs. Reach out to us anytime!</p>
      </div>
    </div>
  </section>

  <!-- Contact Content -->
  <section class="contact-content">
    <div class="container">
      <div class="contact-main">
        <div class="contact-grid">
          <!-- Contact Information -->
          <div class="contact-info">
            <h2>Contact Information</h2>
            <p>Find us easily and stay connected. We're committed to providing excellent service and support.</p>

            <div class="info-item">
              <div class="info-icon">🏪</div>
              <div class="info-content">
                <h3>Business Name</h3>
                <p>MJ's Pharmacy</p>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon">📍</div>
              <div class="info-content">
                <h3>Address</h3>
                <p>Malabor Tibiao, Antique</p>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon">📞</div>
              <div class="info-content">
                <h3>Contact Number</h3>
                <p>09567460163</p>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon">⏰</div>
              <div class="info-content">
                <h3>Operating Hours</h3>
                <p>Monday – Saturday<br>8:00 AM – 8:00 PM</p>
              </div>
            </div>
          </div>

          <!-- Interactive Section -->
          <div class="interactive-section">
            <h2>Quick Actions</h2>

            <div class="action-cards">
              <div class="action-card">
                <img src="{{ asset('image/image.png') }}" alt="QR Code" class="qr-image" />
                <h3>Scan & Pay</h3>
                <p>Quick and convenient payment method. Scan our QR code for instant transactions.</p>
              </div>

              <div class="action-card">
                <div class="feedback-icon">✉️</div>
                <h3>Share Your Feedback</h3>
                <p>Your opinion matters to us. Help us improve our services by sharing your experience.</p>
                <a href="/home/messages" class="feedback-link">Click Here</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  @stack('scripts')
</body>
</html>
