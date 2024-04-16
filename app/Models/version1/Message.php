<?php

namespace App\Models\version1;

use App\Http\Controllers\version1\UtilController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $appends = ['nice_date'];

    //define accessor
    public function getNiceDateAttribute()
    {
        return UtilController::reformatDate($this->created_at, "M j");
        
    }

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'message_id';

    protected $fillable = [
        'message_id',
        'message_text',
        'message_sender_user_id',
        'message_receiver_id',
        'created_at',
        'updated_at',
    ];
}
