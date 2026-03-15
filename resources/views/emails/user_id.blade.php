<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your User ID</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .header {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
        }

        .content {
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Account Information</h2>
        </div>
        <div class="content">
            <p>Hello {{ $username }},</p>
            <p>Your User ID is: <strong>{{ $userId }}</strong></p>
            <p>If you didn’t request this, please contact our support team.</p>
        </div>
        <div class="footer">
            <p>Thank you, <br>Your Company Support Team</p>
        </div>
    </div>
</body>

</html>
