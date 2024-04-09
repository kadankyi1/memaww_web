<?php

namespace App\Models\version1;

use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\version1\Order;

class User extends Authenticatable
{    
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_sys_id',
        'user_first_name',
        'user_last_name',
        'user_phone',
        'user_country_id',
        'user_notification_token_android',
        'user_notification_token_web',
        'user_notification_token_ios',
        'user_android_app_version_code',
        'user_ios_app_version_code',
        'user_flagged',
        'user_flagged_reason',
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

    public function userOrders(){
        return $this->hasMany(Order::class, 'order_user_id');
    }


}
