<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}" />
</head>

<body>
    <div class="form-container">
        <div class="form-box">
            <a href="javascript:history.back()" class="return-icon-btn" title="Go Back">←</a>

            <h2>Welcome Back</h2>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('customer.login') }}" novalidate>
                @csrf

                <div class="input-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile" placeholder="09123456789" required
                        pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number" autocomplete="tel" />
                    <div class="input-hint">Format: 09123456789 (11 digits)</div>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter your password" required
                            autocomplete="current-password" />
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)"
                            aria-label="Show password">Show</button>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} />
                        Remember me
                    </label>
                    <a href="{{ route('password.reset') }}" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="submit-btn">Login</button>
            </form>

            <div class="signup-link">
                Don't have an account? <a href="{{ route('signup.step_one') }}">Sign Up</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                btn.textContent = "Hide";
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                field.type = "password";
                btn.textContent = "Show";
                btn.setAttribute('aria-label', 'Show password');
            }
        }

        // Form validation and mobile number formatting
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[required]');

            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.style.borderColor = '#dc2626';
                    } else {
                        this.style.borderColor = '#16a34a';
                    }
                });

                input.addEventListener('input', function() {
                    if (this.style.borderColor === 'rgb(220, 38, 38)') {
                        this.style.borderColor = '#e5e7eb';
                    }
                });
            });

            // Phone number formatting
            const mobileInput = document.getElementById('mobile');
            mobileInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }
                e.target.value = value;
            });
        });
    </script>
</body>

</html>
