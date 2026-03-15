<h2>Welcome to {{ config('app.name') }}!</h2>

<p>Dear {{ $name }},</p>

<p>Your healthcare professional account has been created successfully.</p>

<p><strong>Login Credentials:</strong></p>
<ul>
    <li><strong>Email:</strong> {{ $email }}</li>
    <li><strong>Password:</strong> {{ $password }}</li>
</ul>

<p>Please change your password after your first login for security purposes.</p>

<p>Regards, <br>
    {{ config('app.name') }} Team
</p>