<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Step 2 | MJ's Pharmacy</title>
    <style>
        /* Enhanced Mobile Responsive Styles */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 1rem;
        }

        .form-box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            position: relative;
            width: 100%;
        }

        .return-icon-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: bold;
            transition: all 0.2s ease;
        }

        .return-icon-btn:hover {
            background: #e5e7eb;
            transform: translateX(-2px);
        }

        h2 {
            color: #333;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            text-align: center;
            padding-top: 1rem;
        }

        .form-subtitle {
            color: #666;
            font-size: 0.875rem;
            text-align: center;
            margin: 0 0 2rem 0;
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .mobile-display {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .input-hint {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding: 0.875rem 3.5rem 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #fafafa;
        }

        .password-wrapper input:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #667eea;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .toggle-password:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .password-requirements {
            margin-top: 0.75rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .password-requirements p {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 1.25rem;
            list-style: none;
        }

        .password-requirements li {
            font-size: 0.8125rem;
            color: #ef4444;
            margin-bottom: 0.25rem;
            position: relative;
            transition: color 0.2s ease;
        }

        .password-requirements li:before {
            content: "×";
            position: absolute;
            left: -1rem;
            font-weight: bold;
        }

        .password-requirements li.valid {
            color: #10b981;
        }

        .password-requirements li.valid:before {
            content: "✓";
        }

        .error {
            color: #dc2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .form-buttons {
            margin-top: 2rem;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .below-button-group {
            text-align: center;
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 1rem;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            margin: 0;
            width: auto;
        }

        .terms-link,
        .privacy-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-link:hover,
        .privacy-link:hover {
            text-decoration: underline;
        }

        .login-link {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Mobile Specific Optimizations */
        @media (max-width: 480px) {
            body {
                align-items: flex-start;
                padding-top: 1rem;
            }

            .form-container {
                padding: 0.75rem;
            }

            .form-box {
                padding: 1.5rem;
                border-radius: 12px;
            }

            .return-icon-btn {
                top: 0.75rem;
                left: 0.75rem;
                width: 36px;
                height: 36px;
                font-size: 1.125rem;
            }

            h2 {
                font-size: 1.5rem;
                padding-top: 0.5rem;
            }

            .password-wrapper input {
                padding: 0.75rem 3.5rem 0.75rem 0.75rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .toggle-password {
                right: 0.5rem;
                font-size: 0.6875rem;
            }

            .submit-btn {
                padding: 0.875rem;
            }

            .checkbox-label {
                text-align: left;
                font-size: 0.8125rem;
            }
        }

        /* Landscape orientation on small devices */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                padding: 0.5rem 0;
            }

            .form-box {
                padding: 1rem;
            }

            .input-group {
                margin-bottom: 1rem;
            }

            .password-requirements {
                margin-top: 0.5rem;
                padding: 0.75rem;
            }
        }

        /* Very small screens */
        @media (max-width: 320px) {
            .form-container {
                padding: 0.5rem;
            }

            .form-box {
                padding: 1rem;
            }

            h2 {
                font-size: 1.25rem;
            }

            .return-icon-btn {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-box">
            <a href="javascript:history.back()" class="return-icon-btn" title="Go Back">←</a>

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

            <form action="{{ route('signup.step_two.submit') }}" method="POST" novalidate>
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
                        <input type="password"
                               name="password"
                               id="password"
                               placeholder="Create Password"
                               required
                               autocomplete="new-password">
                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('password', this)"
                                aria-label="Show password">Show</button>
                    </div>
                    @error('password') <div class="error">{{ $message }}</div> @enderror

                    <div class="password-requirements">
                        <p>Password must:</p>
                        <ul>
                            <li id="length-req">Be at least 8 characters long</li>
                            <li id="uppercase-req">Include at least one uppercase letter</li>
                            <li id="number-req">Include at least one number</li>
                        </ul>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               placeholder="Confirm Password"
                               required
                               autocomplete="new-password">
                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('password_confirmation', this)"
                                aria-label="Show password">Show</button>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="submit-btn">Create Account</button>

                    <div class="below-button-group">
                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="terms"
                                   {{ old('terms') ? 'checked' : '' }}
                                   required>
                            <span>I agree to the
                                <a href="#" class="terms-link">Terms</a> and
                                <a href="#" class="privacy-link">Privacy Policy</a>.
                            </span>
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
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            const confirmPasswordInput = document.querySelector('input[name="password_confirmation"]');
            const requirements = document.querySelector('.password-requirements');

            // Password requirements validation
            passwordInput.addEventListener('input', function() {
                const value = passwordInput.value;
                const lengthReq = document.getElementById('length-req');
                const uppercaseReq = document.getElementById('uppercase-req');
                const numberReq = document.getElementById('number-req');

                // Reset all requirements
                [lengthReq, uppercaseReq, numberReq].forEach(req => {
                    req.classList.remove('valid');
                });

                // Check each requirement
                if (value.length >= 8) {
                    lengthReq.classList.add('valid');
                }
                if (/[A-Z]/.test(value)) {
                    uppercaseReq.classList.add('valid');
                }
                if (/[0-9]/.test(value)) {
                    numberReq.classList.add('valid');
                }
            });

            // Password confirmation validation
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value && confirmPasswordInput.value) {
                    if (passwordInput.value === confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#16a34a';
                    } else {
                        confirmPasswordInput.style.borderColor = '#dc2626';
                    }
                }
            });

            passwordInput.addEventListener('input', function() {
                if (confirmPasswordInput.value) {
                    if (passwordInput.value === confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#16a34a';
                    } else {
                        confirmPasswordInput.style.borderColor = '#dc2626';
                    }
                }
            });
        });

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
    </script>
</body>
</html>
