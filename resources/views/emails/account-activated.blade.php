<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activated</title>
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
                            <p style="margin: 6px 0 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Expense Management & Disbursement Portal</p>
                        </td>
                    </tr>
                    
                    <!-- Content Body -->
                    <tr>
                        <td style="padding: 36px 32px;">
                            <div style="text-align: center; margin-bottom: 24px;">
                                <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background-color: #ecfdf5; border: 2px solid #10b981; font-size: 26px; color: #059669;">
                                    ✓
                                </div>
                                <h2 style="margin: 16px 0 6px; color: #0f172a; font-size: 20px; font-weight: 800;">Account Activated!</h2>
                                <p style="margin: 0; font-size: 13px; color: #64748b;">Your account has been verified and your password is set.</p>
                            </div>

                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                                Hello <strong>{{ $user->name }}</strong>, welcome to SmartClaim! You are now fully authorized to file new reimbursement claims, track claim approvals from your Manager and Finance team, and receive direct disbursements.
                            </p>

                            <!-- Profile Details Card -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 28px;">
                                <table width="100%" style="font-size: 13px; line-height: 1.7;" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600; width: 40%; padding: 4px 0;">Registered Name:</td>
                                        <td style="color: #0f172a; font-weight: 700; padding: 4px 0;">{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600; padding: 4px 0;">Login Email:</td>
                                        <td style="color: #0f172a; font-weight: 700; padding: 4px 0;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600; padding: 4px 0;">Assigned Role:</td>
                                        <td style="padding: 4px 0;">
                                            <span style="background-color: #e0f2fe; color: #0369a1; font-weight: 800; font-size: 11px; padding: 3px 8px; border-radius: 6px; text-transform: uppercase;">
                                                {{ $user->role ?? 'Staff' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600; padding: 4px 0;">Disbursement Bank:</td>
                                        <td style="color: #0f172a; font-weight: 700; padding: 4px 0;">{{ $user->bank_name ?? 'Maybank' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600; padding: 4px 0;">Account No:</td>
                                        <td style="color: #0f172a; font-weight: 700; font-family: monospace; padding: 4px 0;">{{ $user->bank_account_no ?? '—' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Action Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $loginUrl }}" style="display: inline-block; background-color: #00e1b1; color: #022c22; text-decoration: none; padding: 14px 32px; font-size: 14px; font-weight: 800; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(0, 225, 177, 0.35);">
                                    Log In to SmartClaim Portal &rarr;
                                </a>
                            </div>

                            <!-- Security Warning -->
                            <p style="margin: 28px 0 0; font-size: 12px; line-height: 1.6; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 18px;">
                                <strong>Security Notice:</strong> If you did not initiate this activation or set up this password, please contact your company administrator or HR personnel immediately.
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
