<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; padding: 20px;">
    <h2>Hello!</h2>
    <p>Your OTP code for email verification is:</p>
    <h1 style="font-size: 32px; background: #f4f4f4; padding: 10px; text-align: center; letter-spacing: 5px;">
        {{ $otp }}
    </h1>
    <p>This code will expire in 10 minutes.</p>
    <p>If you didn't request this code, please ignore this email.</p>
    <p>Thank you,<br>{{ config('app.name') }} Team</p>
</body>
</html>
