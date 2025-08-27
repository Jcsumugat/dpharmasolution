<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging in...</title>
    <style>
        body {
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .loader-container {
            padding: 40px 60px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        .loader-container h2 {
            font-size: 22px;
            color: #222;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #cce;
            border-top: 6px solid #5f27cd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>

    <script>
        setTimeout(function() {
            window.location.href = "{{ route('dashboard') }}";
        }, 2000);
    </script>
</head>
<body>
    <div class="loader-container">
        <h2>Logging in, please wait...</h2>
        <div class="spinner"></div>

    </div>
</body>
</html>
