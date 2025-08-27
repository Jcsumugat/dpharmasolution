<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Prescription Order Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7f9fc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 30px 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            text-align: center;
        }
        h2 {
            color: #007bff;
            margin-bottom: 25px;
        }
        .info {
            margin-bottom: 15px;
            text-align: left;
        }
        .label {
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
            color: #555;
        }
        .value {
            margin-bottom: 12px;
            color: #333;
        }
        .status-note {
            background-color: #eef6ff;
            border-left: 5px solid #007bff;
            padding: 15px;
            margin: 25px 0 30px;
            border-radius: 8px;
            color: #333;
            text-align: left;
        }
        .btn-home {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,123,255,0.3);
        }
        .btn-home:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 40px;
            font-size: 0.9em;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📄 Prescription Status</h2>

        <div class="info">
            <span class="label">📱 Mobile Number:</span>
            <div class="value">{{ $prescription->mobile_number }}</div>
        </div>

        <div class="info">
            <span class="label">📝 Notes:</span>
            <div class="value">{{ $prescription->notes ?? 'None' }}</div>
        </div>

        <div class="info">
            <span class="label">🆔 Order ID:</span>
            <div class="value">{{ $prescription->order_id ?? 'No order ID' }}</div>
        </div>

        <div class="info">
            <span class="label">📌 Status:</span>
            <div class="value"><strong>{{ ucfirst($prescription->status) }}</strong></div>
        </div>

        <div class="status-note">
            @if ($prescription->status === 'pending')
                <p>⏳ Your prescription is still being processed. Please wait for confirmation.</p>
            @elseif ($prescription->status === 'approved')
                <p>✅ Your order has been approved. Please show your QR code at MJ’s Pharmacy to claim your order.</p>
            @elseif ($prescription->status === 'partially approved')
                <p>⚠️ Some items are currently out of stock. Please check your SMS and confirm if you want to proceed with the available items.</p>
            @elseif ($prescription->status === 'cancelled')
                <p>❌ Your order has been cancelled. Please contact MJ’s Pharmacy for assistance.</p>
            @elseif ($prescription->status === 'completed')
                <p>✔️ Your order has been received. Thank you for choosing MJ’s Pharmacy!</p>
            @else
                <p>ℹ️ Status unknown. Please contact the pharmacy for more information.</p>
            @endif
        </div>

        <a href="{{ url('/') }}" class="btn-home" role="button">🏠 Back to Home</a>

        <div class="footer">
            MJ’s Digital Pharma • {{ date('Y') }}
        </div>
    </div>
</body>
</html>
