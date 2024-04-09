@component('mail::message')
# Login Code


A login attempt has been made on your account.
Use 
<br><h2 style="color: black;">{{ $data['reset_code'] }}</h2>

to complete login.



Thank you,<br>
{{ config('app.name') }}
@endcomponent