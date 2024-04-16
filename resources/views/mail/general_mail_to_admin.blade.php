@component('mail::message')

# Message
{{ $email_data['message_text'] }}


# User's Name
{{ $email_data['user_name'] }}


# User's ID
{{ $email_data['user_id'] }}


# User's Phone
{{ $email_data['user_phone'] }}


<br>
{{ config('app.name') }} APP
@endcomponent
