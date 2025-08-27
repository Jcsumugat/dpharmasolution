<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/style-reset.css') }}">
    <style>
        body {
            background: #e6f2ff;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }

        h2 {
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }

        .email-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .email-wrapper input[type="email"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px 0 0 5px;
            border-right: none;
        }

        .email-wrapper button {
            padding: 10px 15px;
            background: #4A5AFE;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }

        .email-wrapper button:hover {
            background: #3747cc;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: #4A5AFE;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background: #3747cc;
        }

        .status {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>

    @if(session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif
    @if(session('error'))
        <p class="error">{{ session('error') }}</p>
    @endif

    <form action="{{ route('password.send-code') }}" method="POST">
        @csrf
        <label>Email:</label>
        <div class="email-wrapper">
            <input type="email" name="email" required>
            <button type="submit">Send Code</button>
        </div>
    </form>

    <form action="{{ route('password.update') }}" method="POST">
        @csrf
        <label>Code:</label>
        <input type="text" name="code" placeholder="Enter 4-digit code" required>

        <label>New Password:</label>
        <input type="password" name="password" required>

        <label>Confirm Password:</label>
        <input type="password" name="password_confirmation" required>

        <input type="hidden" name="email" value="{{ session('reset_email') }}">
        <button type="submit" class="btn">Reset Password</button>
    </form>
</div>
</body>
</html>
