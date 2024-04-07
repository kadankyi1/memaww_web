<?php

namespace App\Models\version1;

use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class LaundryServiceProvider extends Model
{    
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'laundryserviceprovider_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'laundryserviceprovider_id',
        'laundryserviceprovider_sys_id',
        'laundryserviceprovider_name',
        'laundryserviceprovider_location_raw',
        'laundryserviceprovider_location_gps',
        'laundryserviceprovider_country_id',
        'laundryserviceprovider_phone_1',
        'laundryserviceprovider_phone_2',
        'laundryserviceprovider_email',
        'laundryserviceprovider_flagged',
        'laundryserviceprovider_flagged_reason',
        'created_at',
        'updated_at',
    ];

}
