<?php

namespace App\Models\version1;

use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Order extends Model
{    
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'order_sys_id',
        'order_user_id',
        'order_laundrysp_id',
        'order_collection_biker_name',
        'order_collection_location_raw',
        'order_collection_location_gps',
        'order_collection_date',
        'order_collection_contact_person_phone',
        'order_dropoff_location_raw',
        'order_dropoff_location_gps',
        'order_dropoff_date',
        'order_dropoff_contact_person_phone',
        'order_dropoff_biker_name',
        'order_lightweightitems_just_wash_quantity',
        'order_lightweightitems_wash_and_iron_quantity',
        'order_bulkyitems_just_wash_quantity',
        'order_bulkyitems_wash_and_iron_quantity',
        'order_being_worked_on_status',
        'order_payment_status',
        'order_payment_details',
        'order_flagged',
        'order_flagged_reason',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

}
