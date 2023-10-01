<?php

namespace App\Models;

use App\Models\GroupChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupChat extends Model
{
    protected $table = 'group_chats';

    use HasFactory;

    protected $fillable = [
        'name',
        'picture',
        'admin_id',
        'creator_id'
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_chat_members');
    }

    public function messages()
    {
        return $this->hasMany(GroupChatMessage::class);
    }

    public function isAdmin(User $user)
    {
        return $this->admin_id == $user->id;
    }
}
