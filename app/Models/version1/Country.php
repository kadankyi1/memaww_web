<?php

namespace App\Models\version1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'country_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id', 
        'country_iso_2char_name',
        'country_real_name',
        'country_nice_name',
        'country_iso_3char_name',
        'country_name_number_code',
        'country_phone_number_code',
        'country_can_get_offers',
        'country_currency_symbol',
        'country_currency_threeletter',
        'country_currency_fullnamename',
        'country_can_order',
        'created_at',
        'updated_at',
    ];


    public function countryUsers(){
        return $this->hasMany(User::class, 'user_country_id', 'country_id');
    }
}
