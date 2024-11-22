@component('mail::message')

# New Order - {{ $email_data['order_id'] }} 
There is a new order that should be worked on.
<br>Status: {{ $email_data['order_status'] }} 
<br>Order DateTime: {{ $email_data['order_time'] }} 

# Pickup Time
{{ $email_data['pickup_time'] }} 

# Pickup Location 
{{ $email_data['pickup_location_raw'] }} - GPS: {{ $email_data['pickup_location_gps'] }}

# User's Name & Phone
{{ $email_data['user_name'] }} : {{ $email_data['user_phone'] }}


# Payment
Amount: {{ $email_data['order_payment_amt'] }}
<br>Payment Status: {{ $email_data['order_payment_status'] }}

# Total Items
Total Items: {{ $email_data['order_total_items'] }}

# Light Weight Items - Total: {{ $email_data['order_total_lightweight_items'] }}
Wash & Fold: {{ $email_data['order_lightweightitems_just_wash_quantity'] }}
<br>Wash & Iron: {{ $email_data['order_lightweightitems_wash_and_iron_quantity'] }}
<br>Just Iron: {{ $email_data['order_lightweightitems_just_iron_quantity'] }}

# Medium Weight Items - Total: {{ $email_data['order_total_lightweight_items'] }}
Wash & Fold: {{ $email_data['order_mediumweightitems_just_wash_quantity'] }}
<br>Wash & Iron: {{ $email_data['order_mediumweightitems_wash_and_iron_quantity'] }}
<br>Just Iron: {{ $email_data['order_mediumweightitems_just_iron_quantity'] }}


# Heavy Weight Items - Total: {{ $email_data['order_total_bulkyweight_items'] }}
Wash & Fold: {{ $email_data['order_bulkyitems_just_wash_quantity'] }}
<br>Wash & Iron: {{ $email_data['order_bulkyitems_wash_and_iron_quantity'] }}


# Notification Time
{{ $email_data['time'] }}
<br>
<br>
<br>
{{ config('app.name') }} App
@endcomponent
