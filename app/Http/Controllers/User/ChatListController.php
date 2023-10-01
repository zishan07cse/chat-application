<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\ChatList;
use App\Models\User\ChatMessage;
use Illuminate\Support\Facades\DB;

class ChatListController extends Controller
{
    public function getChatList($id)
    {
        $chatListClass = new ChatList();
        $chatLists= $this->getChatLists($id);
        // return count($chatLists);
        return ['success'=>true, 'data'=>$chatLists];
        
    }

    
    public function sendMessages(Request $request)
    {
       
        $chatListClass = new ChatList();
        $chatMessageClass = new ChatMessage();
        $chatListId= $chatListClass->checkChatExist($request);
        if($request->isfile =='yes'){
            $image = $request->file('image');
            $name=$image->getClientOriginalName();
        
            $ext = strtolower($image->getClientOriginalExtension());
            $directory = public_path().'/chat/images'; 
            $fileName = round(microtime(true)) .rand(2,50). '.' .$ext;
            $image ->move($directory, $fileName);
            $path ="public/chat/images/". $fileName;
            
            $chatMessageClass->sendMessage($chatListId,$request,$path);
        }else{
            
        $message= $chatMessageClass->sendMessage($chatListId,$request,'');
        }
        $chatList = $chatListClass->getSingleChatList($chatListId);
        if($request->sender=='admin'){
        $chatListClass->updateLastMessage($chatListId,$request->message, 0,$chatList->user_count+1);
            
        }else{
            $chatListClass->updateLastMessage($chatListId,$request->message, $chatList->admin_count+1,0);
        }
        return ['success'=>true, 'message'=>"Message Added"];
    }
    // public function sendMessages(Request $request)
    // {
    //     $chatListClass = new ChatList();
    //     $chatMessageClass = new ChatMessage();
    //     $chatListId= $this->checkChatExist($request);
    //     if($request->isfile =='yes'){
    //         $image = $request->file('image');
    //         $name=$image->getClientOriginalName();
        
    //         $ext = strtolower($image->getClientOriginalExtension());
    //         $directory = public_path().'/chat/images'; 
    //         $fileName = round(microtime(true)) .rand(2,50). '.' .$ext;
    //         $image ->move($directory, $fileName);
    //         $path ="public/chat/images/". $fileName;
            
    //         $this->sendMessage($chatListId,$request,$path);
    //     }else{
            
    //     $message= $this->sendMessage($chatListId,$request,'');
    //     }
    //     $this->updateLastMessage($chatListId,$request->message);
    //     return ['success'=>true, 'message'=>"Message Added"];
    // }

    // public function sendMessage($id,$request, $path)
    // {

    //     $message = new ChatMessage();
    //     $message->chat_list_id  = $id;
    //     $message->sender_id= $request->sender_id;
    //     $message->receiver_id = $request->receiver_id; // receiver_id
    //     $message->message = $request->message;
    //     $message->path = $path;
    //     $message->type = $request->type;
    //     $message->status=1;
    //     $message->save();
    // }

    public function updateLastMessage($id, $message)
    {
        $chatListClass = ChatList::find($id);              
        $chatListClass->last_message = $message;
        $chatListClass->save();
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
                $chatListClass->last_message = $request->message;
                $chatListClass->block = 1;
                $chatListClass->status = 1;
                $chatListClass->allow_call = 1;
                $chatListClass->type = 1;
                $chatListClass->last_message = "";
                $chatListClass->save();
                return $chatListClass->id;
            }
        }
        
    }
    
    public function getChatLists($id)
    {
        $chatList =  DB::table('chat_lists')
        ->select('chat_lists.*',
         'users.*', 
        'chat_lists.id as chat_list_id',
        'chat_lists.updated_at as date',)
        ->join('users', 'users.id', '=','chat_lists.receiver_id' )
        ->where('chat_lists.sender_id', $id)
        ->orderBy('chat_lists.updated_at', 'DESC')
        ->get();

        $chatList2 =  DB::table('chat_lists')
        ->select('chat_lists.*',
         'users.*', 
        'chat_lists.id as chat_list_id',
        'chat_lists.updated_at as date',)
        ->join('users', 'users.id', '=','chat_lists.sender_id' )
        ->where('chat_lists.receiver_id', $id)
        ->orderBy('chat_lists.updated_at', 'DESC')
        ->get();
        $data = $chatList2->concat($chatList);
        return $data;
    }
}
