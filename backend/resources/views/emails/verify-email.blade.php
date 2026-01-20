<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }

        h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0 0 20px 0;
        }

        .content {
            margin-bottom: 30px;
        }

        p {
            margin: 0 0 15px 0;
            color: #4b5563;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #1d4ed8;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .link {
            color: #2563eb;
            word-break: break-all;
        }

        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .warning-text {
            color: #92400e;
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
        </div>

        <h1>Verify Your Email Address</h1>

        <div class="content">
            <p>Hello {{ $user->first_name }},</p>

            <p>Thank you for registering with {{ config('app.name') }}. Please click the button below to verify your
                email address and complete your registration.</p>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
            </div>

            <p>If the button doesn't work, you can copy and paste the following link into your browser:</p>
            <p><a href="{{ $verificationUrl }}" class="link">{{ $verificationUrl }}</a></p>

            <div class="warning">
                <p class="warning-text">
                    <strong>Important:</strong> This verification link will expire in
                    {{ config('auth.verification.expire', 60) }} minutes. If you did not create an account, please
                    ignore this email.
                </p>
            </div>

            <p>If you're having trouble verifying your email, please contact our support team.</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>

</html>
