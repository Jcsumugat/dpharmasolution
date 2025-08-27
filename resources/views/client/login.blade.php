<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}" />
</head>
<body>
    <div class="form-container">
        <div class="form-box">
            <a href="javascript:history.back()" class="return-icon-btn" title="Go Back">
            ←
            </a>

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
            
            <form method="POST" action="{{ route('customer.login') }}">
                @csrf
                
                <div class="input-group">
                    <input type="tel" name="mobile" placeholder="Mobile Number" required pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number" />
                    <div class="input-hint">Example: 09123456789</div>
                </div>
                
                <div class="input-group password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Password" required />
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">Show</button>
                </div>

                <div class="remember-forgot" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
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
            } else {
                field.type = "password";
                btn.textContent = "Show";
            }
        }
    </script>
</body>
</html>
