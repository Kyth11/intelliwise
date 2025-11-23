Hi {{ $guardianName }},

Your parent/guardian account for {{ config('app.name') }} has been created for student {{ $studentName }}.

Portal URL: {{ $appUrl }}
Username:   {{ $username }}
Password:   {{ $password }}

Next steps:
1. Open the portal URL above.
2. Sign in using your username and temporary password.
3. Change your password after your first login.

If you already have login credentials from a previous enrollment, you may continue using your existing account.

— {{ config('app.name') }}
