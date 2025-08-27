<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Digital Pharma</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"><animateTransform attributeName="transform" type="translate" values="0,0;50,30;0,0" dur="20s" repeatCount="indefinite"/></circle><circle cx="800" cy="300" r="150" fill="url(%23a)"><animateTransform attributeName="transform" type="translate" values="0,0;-30,50;0,0" dur="25s" repeatCount="indefinite"/></circle><circle cx="300" cy="800" r="80" fill="url(%23a)"><animateTransform attributeName="transform" type="translate" values="0,0;40,-20;0,0" dur="18s" repeatCount="indefinite"/></circle></svg>') center/cover;
            pointer-events: none;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: flex;
            min-height: 600px;
            position: relative;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .left-panel {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .right-panel {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(10px, 10px); }
        }

        .brand-section {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .brand-icon i {
            font-size: 32px;
            color: white;
        }

        .brand-title {
            color: white;
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 300;
            letter-spacing: 2px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: #718096;
            font-size: 1rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .password-toggle:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-password a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-top: 32px;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            border-left: 4px solid #fc8181;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-pill {
            position: absolute;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float-pills 15s infinite linear;
        }

        .floating-pill:nth-child(1) {
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .floating-pill:nth-child(2) {
            left: 50%;
            animation-delay: 5s;
            animation-duration: 18s;
        }

        .floating-pill:nth-child(3) {
            left: 80%;
            animation-delay: 10s;
            animation-duration: 22s;
        }

        @keyframes float-pills {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
                margin: 10px;
            }

            .right-panel {
                min-height: 200px;
                order: -1;
            }

            .left-panel {
                padding: 40px 30px;
            }

            .brand-title {
                font-size: 2rem;
            }

            .login-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="left-panel">
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your admin account</p>
            </div>

            <form method="POST" action="{{ route('admin.login') }}" id="loginForm">
                @csrf
                
                @if(session('error'))
                    <div class="error-message">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="{{ old('email') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <div class="forgot-password">
                        <a href="{{ url('/reset-password') }}">Forgot your password?</a>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <span id="buttonText">Sign In</span>
                </button>
            </form>
        </div>

        <div class="right-panel">
            <div class="floating-elements">
                <div class="floating-pill"></div>
                <div class="floating-pill"></div>
                <div class="floating-pill"></div>
            </div>
            
            <div class="brand-section">
                <div class="brand-icon">
                    <i class="fas fa-pills"></i>
                </div>
                <h1 class="brand-title">Digital<br>Pharma<br>Solution</h1>
                <p class="brand-subtitle">ADMIN PORTAL</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function showForgotPassword() {
            alert('Forgot password functionality would redirect to password reset page.');
        }

        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.querySelector('.login-button');
            const buttonText = document.getElementById('buttonText');
            
            // Show loading state
            button.disabled = true;
            buttonText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
        });

        // Add input focus animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.matches('.form-input')) {
                const inputs = Array.from(document.querySelectorAll('.form-input'));
                const currentIndex = inputs.indexOf(e.target);
                if (currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                } else {
                    document.querySelector('.login-button').click();
                }
            }
        });
    </script>
</body>
</html>