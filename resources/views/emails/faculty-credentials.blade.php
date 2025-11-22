{{-- resources/views/emails/faculty-credentials.blade.php --}}

@php
    $name = trim(($faculty->f_firstname ?? '') . ' ' . ($faculty->f_lastname ?? '')) ?: 'Faculty';
@endphp

<p>Good day {{ $name }},</p>

<p>
    Your faculty account for Intelliwise Grace Christian Academy has been created.
    Below are your login credentials:
</p>

<ul>
    <li><strong>Username:</strong> {{ $username }}</li>
    <li><strong>Password:</strong> {{ $plainPassword }}</li>
</ul>

<p>
    You may log in at:
    <a href="{{ url('/login') }}">{{ url('/login') }}</a>
</p>

<p>
    For security, please sign in as soon as possible and change your password.
</p>

<p>Thank you.</p>

<p>
    <strong>Intelliwise Grace Christian Academy</strong><br>
    Zone 3, Bonbon, Opol, Misamis Oriental
</p>
