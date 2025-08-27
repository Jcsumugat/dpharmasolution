<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback - MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/clienthome.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/feedback.css') }}">
</head>

<body>
    @include('client.client-header')

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>We Value Your Feedback</h1>
                <p class="subtitle">Your thoughts help us serve you better. Please share your experience with MJ's Pharmacy.</p>
            </div>
        </div>
    </section>

    <!-- Feedback Form Section -->
    <div class="container">
        <div class="main-content">
            <section class="feedback-section">
                <div class="section-header">
                    <h2 class="section-title">Share Your Feedback</h2>
                    <p class="section-subtitle">Tell us what you liked, what we can improve, or any suggestions you may have.</p>
                </div>

                <form action="/submit-feedback" method="POST" class="feedback-form">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="message">Your Feedback</label>
                        <textarea id="message" name="message" rows="5" placeholder="Write your feedback here..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Thank You for Helping Us Improve</h2>
            <p>Your feedback helps us provide the best healthcare services possible.</p>
            <div class="cta-buttons">
                <a href="/home/products" class="cta-btn">🛒 Shop Now</a>
                <a href="/home/contact-us" class="cta-btn">📍 Contact Us</a>
            </div>
        </div>
    </section>

    @stack('scripts')
</body>

</html>