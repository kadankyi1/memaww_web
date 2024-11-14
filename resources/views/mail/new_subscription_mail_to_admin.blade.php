@component('mail::message')

# New Subscription - {{ $email_data['subs_id'] }} 
There is a new subscription. Add this to the pickup routine
<br> DateTime: {{ $email_data['subs_time'] }} 

# Number Of People In Home
{{ $email_data['num_of_people'] }} 

# Number Of Months 
{{ $email_data['num_of_months'] }}

# Pickup Location
{{ $email_data['pickup_location'] }}

# Pickup Time & Day
{{ $email_data['pickup_time'] }}

# User's Name & Phone
{{ $email_data['user_name'] }} : {{ $email_data['user_phone'] }}

# Payment
Amount: {{ $email_data['subs_payment_amt'] }}
<br>Payment Status: {{ $email_data['subs_payment_status'] }}

# Notification Time
{{ $email_data['time'] }}
<br>
<br>
<br>
{{ config('app.name') }} App
@endcomponent
