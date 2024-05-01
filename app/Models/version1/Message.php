<?php

namespace App\Models\version1;

use App\Http\Controllers\version1\UtilController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $appends = ['nice_date', 'message_received', 'message_id_string'];

    //define accessor
    public function getNiceDateAttribute()
    {
        return UtilController::reformatDate($this->created_at, "M j");
        
    }

    //define accessor
    public function getMessageReceivedAttribute()
    {
        if($this->message_sender_user_id == 1){
            return true;
        } else {
            return false;
        }
        
    }

    //define accessor
    public function getMessageIdStringAttribute()
    {
        return strval($this->message_id);
        
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
