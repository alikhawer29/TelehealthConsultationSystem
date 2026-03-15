<!DOCTYPE html>
<html>

<head>
    <title>Verify Your Email Address</title>
</head>

<body>
    <p>Hello {{ $name }},</p>
    <p>Thank you for registering. Please verify your email address by clicking the link below:</p>
    <a href="{{ $verificationUrl }}">Verify Email Address</a>
    <p>If you did not create an account, no further action is required.</p>
</body>

</html>
