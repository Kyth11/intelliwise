<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Parent/Guardian Account Credentials</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="background-color:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e0e0e0;">
                {{-- Header with logo --}}
                <tr>
                    <td style="padding:20px 24px 14px 24px;text-align:center;background-color:#0b4c8c;">
                        <img src="{{ asset('images/Intelliwise.png') }}"
                             alt="Intelliwise Grace Christian Academy"
                             style="max-width:160px;height:auto;display:block;margin:0 auto 10px;">
                        <div style="color:#f5f5f5;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">
                            Parent / Guardian Portal
                        </div>
                    </td>
                </tr>

                {{-- Greeting + intro --}}
                <tr>
                    <td style="padding:20px 24px 8px 24px;
                               font-family:Arial,Helvetica,sans-serif;
                               font-size:14px;color:#333333;">
                        <p style="margin:0 0 10px 0;">Hi {{ $guardianName }},</p>
                        <p style="margin:0 0 10px 0;">
                            Your parent/guardian account for
                            <strong>{{ config('app.name') }}</strong> has been created for student
                            <strong>{{ $studentName }}</strong>.
                        </p>
                    </td>
                </tr>

                {{-- Credentials card --}}
                <tr>
                    <td style="padding:0 24px 16px 24px;
                               font-family:Arial,Helvetica,sans-serif;
                               font-size:14px;color:#333333;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse;">
                            <tr>
                                <td style="padding:12px 16px;
                                           border-radius:6px;
                                           background-color:#f5f7fb;
                                           border:1px solid #dde3f0;">
                                    <p style="margin:0 0 8px 0;font-weight:bold;font-size:14px;">
                                        Your login details
                                    </p>
                                    <p style="margin:0 0 4px 0;">
                                        <strong>Portal URL:</strong>
                                        <a href="{{ $appUrl }}" style="color:#0b4c8c;text-decoration:none;">
                                            {{ $appUrl }}
                                        </a>
                                    </p>
                                    <p style="margin:0 0 4px 0;">
                                        <strong>Username:</strong> {{ $username }}
                                    </p>
                                    <p style="margin:0;">
                                        <strong>Temporary password:</strong> {{ $password }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Next steps --}}
                <tr>
                    <td style="padding:0 24px 16px 24px;
                               font-family:Arial,Helvetica,sans-serif;
                               font-size:13px;color:#333333;">
                        <p style="margin:0 0 8px 0;"><strong>Next steps:</strong></p>
                        <ol style="margin:0 0 10px 20px;padding:0;">
                            <li>Open the portal URL above.</li>
                            <li>Sign in using your username and temporary password.</li>
                            <li>Go to your account/profile page and change your password.</li>
                        </ol>
                        <p style="margin:0 0 4px 0;font-size:12px;color:#777777;">
                            If you already have login credentials from a previous enrollment,
                            you may continue using your existing account.
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:0 24px 20px 24px;
                               font-family:Arial,Helvetica,sans-serif;
                               font-size:12px;color:#777777;
                               border-top:1px solid #eeeeee;">
                        <p style="margin:10px 0 0 0;">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
