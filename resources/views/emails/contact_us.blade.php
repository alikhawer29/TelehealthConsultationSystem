<h1>New Contact Us Query</h1>

<p><strong>Name:</strong> {{ $data['name'] }}</p>
<p><strong>Email:</strong> {{ $data['email'] }}</p>
<p><strong>Contact Number:</strong> {{ $data['contact_no'] }}</p>
<p><strong>Message:</strong></p>
<p style="margin-left: 15px;">{{ $data['message'] }}</p>

<hr>
<p>This message was submitted through the Contact Us form on the {{ config('app.name') }} website.</p>
<p><a href="{{ url('/') }}" target="_blank">{{ config('app.name') }} Website</a></p>

<p>Best regards,<br>
    The {{ config('app.name') }} Team</p>