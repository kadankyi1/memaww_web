<?php

namespace App\Models\version1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionCallBackRequest extends Model
{
    use HasFactory;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'col_callback_req_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'col_callback_req_id',
        'col_callback_req_status',
        'col_callback_req_status_message',
        'col_callback_req_user_id',
        'created_at',
        'updated_at',
    ];
}
