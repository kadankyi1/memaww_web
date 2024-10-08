<?php

namespace App\Models\version1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\version1\Country;

class Subscription extends Model
{
    use HasFactory;
    protected $appends = ['subscription_currency'];

        //define accessor
        public function getSubscriptionCurrencyAttribute()
        {
            return Country::where('country_id', '=', $this->subscription_country_id)->first()->country_currency_symbol;
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
