<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Parent/Guardian Credentials</title>
</head>
<body>
    <p>Hi {{ $guardianName }},</p>

    <p>Your parent/guardian account for <strong>{{ config('app.name') }}</strong> has been created for student
    <strong>{{ $studentName }}</strong>.</p>

    <p>
        <strong>Login URL:</strong> <a href="{{ $appUrl }}">{{ $appUrl }}</a><br>
        <strong>Username:</strong> {{ $username }}<br>
        <strong>Password:</strong> {{ $password }}
    </p>

    <p>For security, please sign in and change your password.</p>

    <p>— {{ config('app.name') }}</p>
</body>
</html>
