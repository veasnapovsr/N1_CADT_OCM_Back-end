<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body style="margin:0; padding:24px; background-color:#f5f7fb; font-family:Arial, sans-serif; color:#1f2937;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px; box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);">
        <h2 style="margin:0 0 16px; font-size:24px; color:#111827;">Password Reset Request</h2>

        <p style="margin:0 0 12px; line-height:1.6;">Hello {{ $user->firstname ?? $user->name ?? 'User' }},</p>

        <p style="margin:0 0 16px; line-height:1.6;">
            We received a request to reset your password. Use the following code or token to continue the reset process.
        </p>

        <div style="margin:24px 0; padding:16px; background:#f3f4f6; border-radius:10px; text-align:center; font-size:18px; font-weight:700; letter-spacing:0.04em; word-break:break-all;">
            {{ $token }}
        </div>

        <p style="margin:0 0 12px; line-height:1.6;">
            If you did not request this change, you can ignore this email.
        </p>

        <p style="margin:24px 0 0; font-size:14px; color:#6b7280;">
            Office of Council Works
        </p>
    </div>
</body>
</html>