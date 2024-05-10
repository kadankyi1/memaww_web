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
    protected $appends = ['all_items', 'order_date', 'order_status_message', 'order_final_amt_with_currency', 'order_id_string', 'order_user_id_string'];

    //define accessor
    public function getAllItemsAttribute()
    {
        return strval($this->order_lightweightitems_just_wash_quantity + $this->order_lightweightitems_wash_and_iron_quantity + $this->order_bulkyitems_just_wash_quantity + $this->order_bulkyitems_wash_and_iron_quantity);

    }
    //define accessor
    public function getOrderDateAttribute()
    {
        return date('M j',strtotime($this->created_at));

    }

    public function getOrderStatusMessageAttribute()
    {
        //0=pending Payment, 
        //1=Pending Pickup Assignment, 
        //2-payment_made_pending_collector_assignment, 
        //3-Collected, 4-Washing, 
        //5-assigned_for_delivery, 
        //6-completed
        if($this->order_status == 0){
            return "Pending Payment";
        } else if($this->order_status == 1){
            return "Pending Pickup Assignment";
        } else if($this->order_status == 2){
            return "Assigned For Pickup";
        } else if($this->order_status == 3){
            return "Picked Up";
        } else if($this->order_status == 4){
            return "Washing";
        } else if($this->order_status == 5){
            return "Assigned For Delivery";
        } else if($this->order_status == 6){
            return "Completed";
        } else if($this->order_status == 7){
            return "Payment Failed";
        } else {
            return "Unknown";
        }
    }

    public function getOrderFinalAmtWithCurrencyAttribute()
    {
        return $this->order_user_countrys_currency . $this->order_final_price_in_user_countrys_currency;
    }

    public function getOrderUserIdStringAttribute()
    {
        return strval($this->order_user_id);
    }


    public function getOrderIdStringAttribute()
    {
        return strval($this->order_id);
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
        'special_instructions',
        'order_country_id',
        'order_user_countrys_currency',
        'order_discount_id',
        'order_discount_amount_in_user_countrys_currency',
        'order_discount_amount_in_dollars_at_the_time',
        'order_final_price_in_user_countrys_currency',
        'order_final_price_in_dollars_at_the_time',
        'order_status',
        'order_payment_method',
        'order_payment_status',
        'order_payment_details',
        'order_picker_name',
        'order_picker_phone',
        'order_deliverer_name',
        'order_deliverer_phone',
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
