<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Pre-order Confirmation</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* full viewport height */
            margin: 0;
        }
        .container {
            max-width: 600px;
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            color: green;
            margin-bottom: 1rem;
        }
        p {
            margin-bottom: 10px;
        }
        a {
            color: blue;
        }
        .qr-code {
            margin: 20px auto;
            width: 250px;
            height: 250px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>✅ Pre-order Validated</h2>
        <p>This pre-order was successfully verified.</p>

        <p><strong>Mobile Number:</strong> {{ $prescription->mobile_number }}</p>
        <p><strong>Notes:</strong> {{ $prescription->notes ?? 'None' }}</p>
        <p><strong>Order ID:</strong> {{ $order->order_id ?? 'Not available' }}</p>
        <p><strong>File:</strong>
            <a href="{{ asset('storage/' . $prescription->file_path) }}" target="_blank">View Prescription</a>
        </p>

        @if(session('qr_image'))
            <img src="{{ session('qr_image') }}" alt="QR Code" class="qr-code" />
        @endif
    </div>
</body>
</html>
