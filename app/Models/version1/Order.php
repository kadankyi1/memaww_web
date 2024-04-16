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
    protected $appends = ['all_items', 'order_date'];

    //define accessor
    public function getAllItemsAttribute()
    {
        return $this->order_lightweightitems_just_wash_quantity + $this->order_lightweightitems_wash_and_iron_quantity + $this->order_bulkyitems_just_wash_quantity + $this->order_bulkyitems_wash_and_iron_quantity;

    }
    //define accessor
    public function getOrderDateAttribute()
    {
        return date('M j',strtotime($this->created_at));

    }

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
        'order_all_items_full_description',
        'order_country_id',
        'order_user_countrys_currency',
        'order_discount_id',
        'order_discount_amount_in_user_countrys_currency',
        'order_discount_amount_in_dollars_at_the_time',
        'order_final_price_in_user_countrys_currency',
        'order_final_price_in_dollars_at_the_time',
        'order_status',
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
