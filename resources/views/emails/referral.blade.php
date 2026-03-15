<h1>Hello {{ $data['name'] }},</h1>
<p>You have been referred as a {{ $data['friend_type'] }}.</p>
<p>Relationship: {{ $data['relationship'] }}</p>
<p>Phone Number: {{ $data['country_code'] }} {{ $data['phone_no'] }}</p>
<p>Visit our website for more details:</p>
<p><a href="https://custom-dev.onlinetestingserver.com/milestone" target="_blank">{{ config('app.name') }} Website</a></p>
<p>Best regards,<br> {{ config('app.name') }} Team</p>
