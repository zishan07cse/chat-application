<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class ChatList extends Model
{
    use HasFactory;
    
    public function getSingleChatList($id){
         $chatList = ChatList::find($id);
         return $chatList;
        
    }
    public function getChatList($id)
    {
        $chatList =  DB::table('chat_lists')
        ->select('chat_lists.*',
         'users.*', 
        'chat_lists.id as chat_list_id',
        'chat_lists.updated_at as date',
        'chat_lists.user_count',
        'chat_lists.admin_count',
        )
        ->join('users', 'users.id', '=','chat_lists.receiver_id' )
        ->where('chat_lists.sender_id', $id)
        ->orderBy('chat_lists.updated_at', 'DESC')
        ->get();

        $chatList2 =  DB::table('chat_lists')
        ->select('chat_lists.*',
         'users.*', 
        'chat_lists.id as chat_list_id',
        'chat_lists.updated_at as date',
        'chat_lists.user_count',
        'chat_lists.admin_count',)
        ->join('users', 'users.id', '=','chat_lists.sender_id' )
        ->where('chat_lists.receiver_id', $id)
        ->orderBy('chat_lists.updated_at', 'DESC')
        ->get();
        $data = $chatList2->concat($chatList);
        return $data;
    }

    public function checkChatExist($request)
    {
        $chatList = ChatList::where('sender_id', $request->sender_id)->where('receiver_id', $request->receiver_id)->first();
        if($chatList != null){
            return $chatList->id;
        }else{
            $checkAgain = ChatList::where('sender_id', $request->receiver_id)->where('receiver_id', $request->sender_id)->first();
            if($checkAgain != null){
                return $checkAgain->id;
            }else{
                $chatListClass = new ChatList();
                $chatListClass->sender_id = $request->sender_id;
                $chatListClass->receiver_id = $request->receiver_id;
                $chatListClass->allow_call = 0;
                $chatListClass->last_message = $request->message;
                $chatListClass->block = 1;
                $chatListClass->type = 1;
                $chatListClass->admin_count = 0;
                $chatListClass->user_count = 0;
                $chatListClass->status = 1;
                $chatListClass->save();
                return $chatListClass->id;
            }
        }
        
    }

    public function updateLastMessage($id, $message, $adminCount, $userCount)
    {
        $chatListClass = ChatList::find($id);              
        $chatListClass->last_message = $message;
        $chatListClass->admin_count = $adminCount;
        $chatListClass->user_count = $userCount;
        $chatListClass->save();
    }
}
