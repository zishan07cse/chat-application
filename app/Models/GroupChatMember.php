<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupChatMember extends Model
{
    protected $table = 'group_chat_members';
    protected $fillable = [
        'group_chat_id', 'user_id', 'is_admin'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }
}
