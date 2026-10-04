<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;" cellspacing="0" cellpadding="0">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background-color: #0b1727; padding: 32px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 900; letter-spacing: -0.5px;">SmartClaim</h1>
                            <p style="margin: 6px 0 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Expense Management & Disbursement System</p>
                        </td>
                    </tr>
                    
                    <!-- Content Body -->
                    <tr>
                        <td style="padding: 36px 32px;">
                            <h2 style="margin: 0 0 16px; color: #0f172a; font-size: 18px; font-weight: 800;">Password Reset Request</h2>
                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                                Hello {{ $user->name }}, we received a request to reset the password for your SmartClaim account associated with <strong>{{ $user->email }}</strong>.
                            </p>

                            <!-- Security Alert Box -->
                            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; border-radius: 0 8px 8px 0; padding: 14px 18px; margin-bottom: 24px;">
                                <p style="margin: 0; font-size: 13px; color: #991b1b; line-height: 1.5;">
                                    <strong>Important Security Notice:</strong> This password reset link will expire in <strong>60 minutes</strong>. If you did not request a password reset, you can safely ignore this email.
                                </p>
                            </div>

                            <!-- Action Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #00e1b1; color: #022c22; text-decoration: none; padding: 14px 32px; font-size: 14px; font-weight: 800; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(0, 225, 177, 0.35);">
                                    Reset Your Password
                                </a>
                            </div>

                            <!-- Fallback Link Notice -->
                            <p style="margin: 28px 0 0; font-size: 12px; line-height: 1.6; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                If the button above does not work, copy and paste this direct link into your browser:<br>
                                <a href="{{ $resetUrl }}" style="color: #0284c7; word-break: break-all;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                &copy; {{ date('Y') }} SmartClaim Expense Management. This is an automated corporate notification.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
