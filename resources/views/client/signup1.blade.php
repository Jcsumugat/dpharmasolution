<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Step 1 | MJ's Pharmacy</title>
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

        h2 {
            color: #333;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            text-align: center;
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

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #fafafa;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-hint {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .gender-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .gender-group input[type="radio"] {
            width: auto;
            margin-right: 0.5rem;
        }

        .gender-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 0;
            cursor: pointer;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .error {
            color: #dc2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
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

        .login-link {
            text-align: center;
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

            h2 {
                font-size: 1.5rem;
            }

            .input-group input,
            .input-group textarea {
                padding: 0.75rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .gender-group {
                flex-direction: column;
                gap: 0.75rem;
            }

            .submit-btn {
                padding: 0.875rem;
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
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-box">
            <h2>Create Account</h2>
            <p class="form-subtitle">Step 1 of 2: Personal Information</p>


            <form action="{{ route('signup.step_one.submit') }}" method="POST" novalidate>
                @csrf

                <div class="input-group">
                    <label for="full_name">Full Name</label>
                    <input type="text"
                           id="full_name"
                           name="full_name"
                           placeholder="Enter your full name"
                           value="{{ old('full_name') }}"
                           required
                           autocomplete="name">
                    @error('full_name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label for="contact_number">Mobile Number</label>
                    <input type="tel"
                           id="contact_number"
                           name="contact_number"
                           placeholder="09123456789"
                           value="{{ old('contact_number') }}"
                           required
                           pattern="[0-9]{11}"
                           title="Please enter a valid 11-digit phone number"
                           autocomplete="tel">
                    @error('contact_number') <div class="error">{{ $message }}</div> @enderror
                    <div class="input-hint">Format: 09123456789 (11 digits)</div>
                </div>

                <div class="input-group">
                    <label for="email_address">Email Address</label>
                    <input type="email"
                           id="email_address"
                           name="email_address"
                           placeholder="your.email@example.com"
                           value="{{ old('email_address') }}"
                           required
                           autocomplete="email">
                    @error('email_address') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label for="birthdate">Date of Birth</label>
                    <input type="date"
                           id="birthdate"
                           name="birthdate"
                           value="{{ old('birthdate') }}"
                           required
                           max="{{ date('Y-m-d', strtotime('-13 years')) }}"
                           autocomplete="bday">
                    @error('birthdate') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label>Gender</label>
                    <div class="gender-group">
                        <label for="male">
                            <input type="radio"
                                   id="male"
                                   name="sex"
                                   value="male"
                                   {{ old('sex') == 'male' ? 'checked' : '' }}>
                            Male
                        </label>

                        <label for="female">
                            <input type="radio"
                                   id="female"
                                   name="sex"
                                   value="female"
                                   {{ old('sex') == 'female' ? 'checked' : '' }}>
                            Female
                        </label>

                        <label for="other">
                            <input type="radio"
                                   id="other"
                                   name="sex"
                                   value="other"
                                   {{ old('sex') == 'other' ? 'checked' : '' }}>
                            Other
                        </label>
                    </div>
                    @error('sex') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label for="address">Complete Address</label>
                    <textarea id="address"
                              name="address"
                              placeholder="Enter your complete address including street, city, and province"
                              rows="3"
                              required
                              autocomplete="street-address">{{ old('address') }}</textarea>
                    @error('address') <div class="error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="submit-btn">Next Step</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="{{ route('login.form') }}">Log in</a>
            </div>
        </div>
    </div>

    <script>
        // Form validation feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[required], textarea[required]');

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
            const phoneInput = document.getElementById('contact_number');
            phoneInput.addEventListener('input', function(e) {
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
