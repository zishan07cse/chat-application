<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupChatMessage extends Model
{
    protected $fillable = [
        'group_chat_id', 'sender_id', 'content', 'type', 'filename', 'filepath'
    ];

    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class);
    }
}
