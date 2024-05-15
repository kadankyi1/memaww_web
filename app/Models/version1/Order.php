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
    protected $appends = [
        'all_items', 
        'order_date', 
        'order_status_message', 
        'order_final_amt_with_currency', 
        'order_id_string', 
        'order_user_id_string', 
        'order_id_long', 
        'order_delivery_date'
    ];

    //define accessor
    public function getAllItemsAttribute()
    {
        return strval($this->order_lightweightitems_just_wash_quantity + $this->order_lightweightitems_wash_and_iron_quantity + $this->order_lightweightitems_just_iron_quantity + $this->order_bulkyitems_just_wash_quantity + $this->order_bulkyitems_wash_and_iron_quantity);

    }
    //define accessor
    public function getOrderDateAttribute()
    {
        return date('F j, g:i a',strtotime($this->created_at));
        //return $this->created_at->diffForHumans;
    }

    public function getOrderStatusMessageAttribute()
    {
        //0=pending Payment, 
        //1=Pending Pickup Assignment, 
        //2-Assigned For Pickup, 
        //3-Picked up, 
        //4-Washing,
        //5-Assigned For Delivery, 
        //6-completed,
        //7-Payment failed
        if($this->order_status == 0){
            return "Your order is pending Payment";
        } else if($this->order_status == 1){
            return "We are yet to assign someone to pickup your laundry";
        } else if($this->order_status == 2){
            return $this->order_picker_name . " has been assigned to pickup your laundry. Call them on " . $this->order_picker_phone;
        } else if($this->order_status == 3){
            return "Your laundry has been picked up";
        } else if($this->order_status == 4){
            return "Your laundry is being washed and packed";
        } else if($this->order_status == 5){
            return $this->order_deliverer_name . " has been assigned to deliver your laundry. Call them on " . $this->order_deliverer_phone;
        } else if($this->order_status == 6){
            return "Order completed. We hope you enjoyed the service";
        } else if($this->order_status == 7){
            return "We had to cancel your order. If this was an error, start a new order or contact us";
        } else {
            return "We don't seem to know what is happening with your order";
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

    public function getOrderIdLongAttribute()
    {
        return "#" . sprintf("%06d", $this->order_id);
    }


    public function getOrderDeliveryDateAttribute()
    {
        return empty($this->order_dropoff_date) == true ? "Pending" : date('F j, g:i a',strtotime($this->created_at));
        //return $this->created_at->diffForHumans;
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
        'order_lightweightitems_just_iron_quantity',
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
