@component('mail::message')
# {{ $data['title'] }} 


<p style="color: black;">{{ $data['message'] }}</p>


Thank you,<br>
Pott Ai - {{ config('app.name') }}<br>
{{ config('app.fishpott_phone') }}<br>
{{ config('app.fishpott_email_two') }}<br>

@endcomponent