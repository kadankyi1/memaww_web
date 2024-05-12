<?php

namespace App\Models\version1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $appends = ["notification_date"];

    public function getNotificationDateAttribute(){
        return date('M j',strtotime($this->created_at));
    }

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'notification_id',
        'notification_title',
        'notification_body',
        'notification_topic_or_receiver_phone',
        'notification_sender_admin_id',
        'created_at',
        'updated_at',
    ];
}
