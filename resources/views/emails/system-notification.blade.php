<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificationTitle }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;" cellspacing="0" cellpadding="0">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background-color: #0b1727; padding: 28px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">SmartClaim</h1>
                            <p style="margin: 4px 0 0; color: #94a3b8; font-size: 12px; font-weight: 500;">Expense Management & Reimbursement Portal</p>
                        </td>
                    </tr>
                    
                    <!-- Content Body -->
                    <tr>
                        <td style="padding: 32px 30px;">
                            @php
                                $badgeBg = '#eff6ff';
                                $badgeColor = '#1d4ed8';
                                $badgeBorder = '#bfdbfe';
                                $badgeIcon = 'ℹ️';

                                if (in_array($notificationType, ['success', 'approved', 'paid', 'reimbursed'])) {
                                    $badgeBg = '#ecfdf5';
                                    $badgeColor = '#047857';
                                    $badgeBorder = '#a7f3d0';
                                    $badgeIcon = '✓';
                                } elseif (in_array($notificationType, ['warning', 'revision', 'audit', 'pending'])) {
                                    $badgeBg = '#fffbeb';
                                    $badgeColor = '#b45309';
                                    $badgeBorder = '#fde68a';
                                    $badgeIcon = '⚠️';
                                } elseif (in_array($notificationType, ['danger', 'error', 'rejected', 'fraud'])) {
                                    $badgeBg = '#fef2f2';
                                    $badgeColor = '#b91c1c';
                                    $badgeBorder = '#fecaca';
                                    $badgeIcon = '✕';
                                }
                            @endphp

                            <!-- Status Badge -->
                            <div style="margin-bottom: 20px;">
                                <span style="display: inline-block; background-color: {{ $badgeBg }}; color: {{ $badgeColor }}; border: 1px solid {{ $badgeBorder }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                    {{ $badgeIcon }} {{ $notificationType }}
                                </span>
                            </div>

                            <h2 style="margin: 0 0 12px; color: #0f172a; font-size: 18px; font-weight: 800; line-height: 1.4;">
                                {{ $notificationTitle }}
                            </h2>

                            <p style="margin: 0 0 20px; font-size: 14px; color: #64748b;">
                                Hello <strong>{{ $user->name }}</strong>, there is an official update regarding your claim or portal activity:
                            </p>

                            <!-- Notification Message Card -->
                            <div style="background-color: #f8fafc; border-left: 4px solid {{ $badgeColor }}; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; border-radius: 8px; padding: 18px 20px; margin-bottom: 26px;">
                                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #1e293b; font-weight: 500;">
                                    {{ $notificationMessage }}
                                </p>
                            </div>

                            @if(!empty($actionUrl))
                                <!-- Action Button -->
                                <div style="text-align: center; margin: 26px 0 16px;">
                                    <a href="{{ $actionUrl }}" style="display: inline-block; background-color: #0b1727; color: #ffffff; text-decoration: none; padding: 12px 28px; font-size: 13px; font-weight: 800; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(11, 23, 39, 0.25);">
                                        View Details in Portal &rarr;
                                    </a>
                                </div>
                            @endif

                            <p style="margin: 24px 0 0; font-size: 12px; line-height: 1.5; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                                You can also view this and all past notifications anytime by tapping the bell icon in your SmartClaim portal navigation bar.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 18px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                &copy; {{ date('Y') }} SmartClaim Expense Management System. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
