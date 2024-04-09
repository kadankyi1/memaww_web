<?php

namespace App\Models\version1;

use Laravel\Passport\HasApiTokens;
use App\Models\version1\User;
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

    public function orderDetails(){

        //customer_id is a foreign key in customer_items table
   
        return $this->hasOne(User::class, 'user_id');
   
                   //An Item will has single detail thats why hasOne relation used here
    }

}
