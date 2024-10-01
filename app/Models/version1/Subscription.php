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
        'subscription_days',
        'subscription_months',
        'subscription_country_id',
        'subscription_amt_per_month',
        'subscription_amt_total',
        'subscription_package_description_1',
        'subscription_package_description_2',
        'subscription_package_description_3',
        'subscription_package_description_4',
        'subscription_adder_admin_name',
        'created_at',
        'updated_at',
    ];

    
}
