<!DOCTYPE html>
<html dir="ltr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; background: #f5f5f5;">
    <div style="max-width: 480px; margin: 0 auto; background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; color: #1a1a1a;">You're invited to TaskFlow</h1>
        <p style="color: #666; font-size: 14px;">{{ $inviterName }} invited you to join the <strong>{{ $workspaceName }}</strong> workspace.</p>
        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ $acceptUrl }}" style="display: inline-block; background: #2563eb; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600;">Accept invitation</a>
        </div>
        <p style="color: #999; font-size: 12px; text-align: center;">This invitation expires in 7 days. If you weren't expecting this, you can ignore this email.</p>
    </div>
</body>
</html>
