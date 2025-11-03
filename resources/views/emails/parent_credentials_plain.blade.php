Hi {{ $guardianName }},

Your parent/guardian account for {{ config('app.name') }} has been created for student {{ $studentName }}.

Login URL: {{ $appUrl }}
Username:  {{ $username }}
Password:  {{ $password }}

For security, please sign in and change your password.

— {{ config('app.name') }}
