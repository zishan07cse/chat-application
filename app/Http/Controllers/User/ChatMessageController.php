<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\ChatList;
use App\Models\User\ChatMessage;

class ChatMessageController extends Controller
{
    public function getMessages(Request $request)
    {
        
        $page = isset($request->page)&& ($request->page > 0) ? (int)$request->page : 1;
      
        $chatMessageClass = new ChatMessage();
        $chatListClass = new ChatList();
        $chatListId= $chatListClass->checkChatExist($request);
        $messages= $this->getChatMessages($chatListId, $page);
        $this->updateMessageCount($chatListId, $request->sender);
        
        return ['success'=>true, 'data'=>$messages];
    }

    public function updateMessageCount($id, $sender)
    {
        
        $list = ChatList::find( $id);
        if($sender=='admin'){
            $list->admin_count=0;
        }else{
            $list->user_count=0;
        }
        $list->save();
    } 
    
    public function getChatMessages($id, $page = 1)
    {
        $perPage = 20;
        $totalMessages = ChatMessage::where('chat_list_id', $id)->count();
        $totalPages = ceil($totalMessages / $perPage);
    
        $offset = ($page - 1) * $perPage;
    
        $messages = ChatMessage::where('chat_list_id', $id)
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->take($perPage)
            ->get();
    
        return [
            'messages' => $messages,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_messages' => $totalMessages
        ];
    }
    
}