<?php

namespace App\Models\version1;

use App\Http\Controllers\version1\UtilController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\version1\Country;

class Subscription extends Model
{
    use HasFactory;
    protected $appends = ['subscription_currency', "pickups_count", "pickup_final_time", "subscription_info", "items_washed_info"];

        //define accessor
        public function getSubscriptionCurrencyAttribute()
        {
            return Country::where('country_id', '=', $this->subscription_country_id)->first()->country_currency_symbol;
        }

        //define accessor
        public function getPickupsCountAttribute()
        {
            return strval($this->subscription_pickups_done)  . " / 4" ;
        }


        //define accessor
        public function getPickupFinalTimeAttribute()
        {
            return strval($this->subscription_pickup_time)  . " - " . strval($this->subscription_pickup_day);
        }

        //define accessor
        public function getSubscriptionInfoAttribute()
        {
            return "Your subscription ends on " . UtilController::getDatePlusOrMinusDays($this->created_at, "+30 days", "F j, Y");
        }

        //define accessor
        public function getItemsWashedInfoAttribute()
        {
            return strval($this->subscription_items_washed)  . " / Unlimited" ;
        }


    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'subscription_id';

    protected $fillable = [
        'subscription_id',
        'subscription_items_washed',
        'subscription_pickups_done',
        'subscription_amount_paid',
        'subscription_max_number_of_people_in_home',
        'subscription_number_of_months',
        'subscription_pickup_time',
        'subscription_pickup_day',
        'subscription_pickup_location',
        'subscription_package_description',
        'subscription_country_id',
        'subscription_payment_transaction_id',
        'subscription_payment_response',
        "subscription_user_id",
        'created_at',
        'updated_at',
    ];

    
}
