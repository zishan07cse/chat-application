<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageStatus extends Model
{
    
    protected $table = 'message_statuses';
    protected $fillable = [
        'message_id', 'user_id', 'last_seen'
    ];

    public function message()
    {
        return $this->belongsTo('App\Models\Message');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
