<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    public function sendMessage($id,$request, $path)
    {
        
        $message = new ChatMessage();
        $message->chat_list_id  = $id;
        $message->sender_id= $request->sender_id;
        $message->message = $request->message;
        $message->path = $path;
        $message->type = $request->type;
        $message->status=1;
        $message->save();
    }

    public function getChatMessages($id)
    {
        $message = ChatMessage::where('chat_list_id', $id)->get();
        return $message;
    }
}
