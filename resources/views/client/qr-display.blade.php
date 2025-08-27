<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>QR Code</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
        }
        img {
            max-width: 90vw;
            max-height: 90vh;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <img src="{{ $qrPath }}" alt="QR Code" />
</body>
</html>
