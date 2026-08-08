<!DOCTYPE html>
<html dir="ltr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; background: #f5f5f5;">
    <div style="max-width: 480px; margin: 0 auto; background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; color: #1a1a1a;">Welcome to TaskFlow!</h1>
        <p style="color: #666; font-size: 14px;">Hi {{ $userName }},</p>
        <p style="color: #666; font-size: 14px;">Use the code below to verify your email address:</p>
        <div style="text-align: center; margin: 32px 0;">
            <span style="font-size: 36px; letter-spacing: 12px; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 16px 24px; border-radius: 8px; font-family: 'Courier New', monospace;">{{ $otpCode }}</span>
        </div>
        <p style="color: #666; font-size: 14px;">This code expires in <strong>10 minutes</strong>.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
        <p style="color: #999; font-size: 12px; text-align: center;">If you didn't request this code, you can safely ignore this email.</p>
    </div>
</body>
</html>
