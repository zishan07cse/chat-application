<?php

namespace App\Http\Controllers;

use App\Models\GroupChatMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

        
    function sendGroupMessage($group_id,$content,$sender_id, $path, $type)
    {    
        $message = new GroupChatMessage();
        $message->group_chat_id = $group_id;
        $message->sender_id = $sender_id;
        $message->content = $content;
        $message->type = $type;
        $message->filename = $path;
        $message->save();
    } 

}
