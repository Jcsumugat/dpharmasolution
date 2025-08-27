<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Step 2 | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
</head>

<body>
    <div class="form-container">
        <div class="form-box">

            <a href="javascript:history.back()" class="return-icon-btn" title="Go Back">
            ←
            </a>
            <h2>Create Account</h2>
            <p class="form-subtitle">Step 2 of 2: Account Details</p>

            {{-- Display session and validation errors --}}
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif

            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif

            <form action="{{ route('signup.step_two.submit') }}" method="POST">
                @csrf

                {{-- Display mobile number from session --}}
                <div class="input-group">
                    <label>Your Mobile Number</label>
                    <div class="mobile-display">
                        {{ session('registration')['contact_number'] ?? 'N/A' }}
                    </div>
                    <div class="input-hint">We'll use this number to log you in</div>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Create Password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">Show</button>
                    </div>
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                    <div class="password-requirements">
                        <p>Password must:</p>
                        <ul>
                            <li>Be at least 8 characters long</li>
                            <li>Include at least one uppercase letter</li>
                            <li>Include at least one number</li>
                        </ul>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation', this)">Show</button>
                    </div>
                </div>
                {{-- Terms and Conditions --}}
                <div class="form-buttons">
                    <button type="submit" class="submit-btn">Create Account</button>

                    <div class="below-button-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" {{ old('terms') ? 'checked' : '' }}>
                            I agree to the
                            <a href="#" class="terms-link">Terms</a> and
                            <a href="#" class="privacy-link">Privacy Policy</a>.
                        </label>
                        @error('terms')
                        <div class="error">{{ $message }}</div>
                        @enderror

                        <div class="login-link">
                            Already have an account? <a href="{{ route('login.form') }}">Log In</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.querySelector('input[name="password"]');
            const requirements = document.querySelector('.password-requirements');

            passwordInput.addEventListener('input', () => {
                const value = passwordInput.value;
                requirements.querySelectorAll('li').forEach(li => li.style.color = 'red');

                if (value.length >= 8)
                    requirements.querySelectorAll('li')[0].style.color = 'green';
                if (/[A-Z]/.test(value))
                    requirements.querySelectorAll('li')[1].style.color = 'green';
                if (/[0-9]/.test(value))
                    requirements.querySelectorAll('li')[2].style.color = 'green';
            });
        });
    </script>
    <script>
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                btn.textContent = "Hide";
            } else {
                field.type = "password";
                btn.textContent = "Show";
            }
        }
    </script>


</body>

</html>