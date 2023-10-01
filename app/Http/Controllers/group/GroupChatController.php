<?php

namespace App\Http\Controllers\group;

use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\GroupChatMember;
use App\Models\GroupChatMessage;
use App\Models\GroupChat;
use App\Models\MessageStatus;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException as ValidationValidationException;
use Nette\Utils\Validators;

class GroupChatController extends Controller
{    

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

    function saveMessageStatus(Request $request, $group_chat_id, $message_id){

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer', 
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $user_id = request('user_id'); 
    
            $message_status = MessageStatus::updateOrCreate(
                ['message_id' => $message_id, 'user_id' => $user_id],
                ['last_seen' => now()]
            );

            return response()->json(['message' => 'Status saved successfully!']);
    }
    
    function getStatusGroupMessages($id) {

        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer', 
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $messages = GroupChatMessage::where('group_chat_id', $id)->orderBy('created_at','DESC')->get();
        $response = [];
        foreach ($messages as $message) {
            $messageResponse = [
                'id' => $message->id,
                'group_chat_id' => $message->group_chat_id,
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'type' => $message->type,
                'filename' => $message->filename,
                'filepath' => $message->filepath,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
                'message_statuses' => []
            ];
    
            $messageStatuses = MessageStatus::where('message_id', $message->id)->get();
            foreach ($messageStatuses as $status) {
                $messageStatus = [
                    'user_id' => $status->user_id,
                    'last_seen' => $status->last_seen,
                    'created_at' => $status->created_at,
                    'updated_at' => $status->updated_at
                ];
                $messageResponse['message_statuses'][] = $messageStatus;
            }
    
            $response[] = $messageResponse;
        }
    
        return response()->json($response);
    }

    function sendMessage(Request $request, $group_id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'content' => 'required|string',
            'file' => 'nullable|file|max:2048',
            'type' => 'required|string',
            'isfile' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $group = GroupChat::findOrFail($group_id);
        
        $user_id = request('user_id');
        $user = User::where('id', $user_id);

        try {
            GroupChatMember::where('group_chat_id', $group_id)
                                                ->where('user_id', $user_id)
                                                ->firstOrFail();
        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized', 'error_obj' => $e], 401);
        }
        
        if ($request->isfile == 'yes') {
            $file = $request->file('file');
            $name = $file->getClientOriginalName();
    
            $ext = strtolower($file->getClientOriginalExtension());
            $directory = public_path().'/chat/files';
            $fileName = round(microtime(true)) . rand(2, 50) . '.' . $ext;
            $file->move($directory, $fileName);
            $path = "public/chat/files/" . $fileName;
    
            $message = $this->sendGroupMessage($group_id, $request->input('content'), $user_id, $path, $request->input('type'));
        } else {
            $message = $this->sendGroupMessage($group_id, $request->input('content'), $user_id, "", $request->input('type'));
        }
    
        return response()->json(['message' => 'Message send successfully'], 201);
    }

    function addGroupMember(Request $request, GroupChat $group, $groupChatId) {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user_id = $request->input('user_id');
        $admin_id = $request->input('admin_id');
        $group = $request->input('admin_id');

        $admin = User::findOrFail($admin_id);
        $user = User::findOrFail($user_id);
        $group = GroupChat::findOrFail($groupChatId);
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        if ($group->admin_id !== $admin->id) {
            return response()->json(['error' => 'You are not authorized to add users to this group.'], 403);
        }
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            $group->members()->attach($user->id);
        }
        return response()->json(['message' => 'User added to the group successfully.'], 200);
    }

    function addGroupAdmin(Request $request, $group_id, $user_id) {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $admin_id = $request->input('admin_id');
        $group = GroupChat::findOrFail($group_id);

        // Check that the current user is the group admin
        if ($group->admin_id != $admin_id ) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {            
            // Check that the user we want to promote is not already an admin
            $groupMember = GroupChatMember::where('group_chat_id', $group_id)
                                            ->where('user_id', $user_id)
                                            ->firstOrFail();
        } catch (Exception $e) {
            return response()->json(['error' => 'User is not member of this group'], 404);
        }        
                                       
        if ($groupMember->is_admin) {
            return response()->json(['error' => 'User is already an admin of this group'], 400);
        }
        
        // Promote the user to admin
        $groupMember->is_admin = true;
        $groupMember->save();
        
        return response()->json(['success' => 'User has been made an admin of this group']);
    }

    function removeGroupMember(Request $request, $group_id, $member_id) {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $admin_id = $request->input('admin_id');
        // Check if authenticated user is the group admin
        $group = GroupChat::findOrFail($group_id);
        if (!$group->members()->where('user_id', $admin_id)->exists()) {
            return response()->json(['message' => 'You are not authorized to perform this action.'], 403);
        }
    
        // Remove member from group
        $member = GroupChatMember::where('group_chat_id', $group_id)->where('user_id', $member_id)->firstOrFail();
        if ($member->is_admin)
            return response()->json(['message' => 'You can\'t remove admin.'], 403);

        $member->delete();
    
        return response()->json(['message' => 'Member removed successfully.'], 200);
    }

    function removeGroupAdmin(Request $request, $groupChatId, $userId){

        try{
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::where('id', $request->input('admin_id'))->firstOrFail();
        $groupChat = GroupChat::findOrFail($groupChatId);
        
        
        try {
            $isAdmin = GroupChatMember::where('group_chat_id', $groupChatId)
                               ->where('user_id', $userId)
                               ->firstOrFail();
        } catch (Exception $e) {
            return response()->json(['error' => 'You are not member of this group'], 404);
        }
        
    
        // Check that the user is the admin of the group
        if (!$isAdmin->is_admin) {
            return response()->json(['error' => 'You must be the administrator of the group to remove a member\'s admin status.'], 400);
        }
        
        try {
            $member = GroupChatMember::where('group_chat_id', $groupChatId)
                                                ->where('user_id', $userId)
                                                ->firstOrFail();
        } catch (Exception $e) {
            return response()->json(['error' => 'This User is not member of this group'], 404);
        }

        // Check that the admin being demote as admin is actually an creator
        if (!$member->is_admin) {
            return response()->json(['error' => 'The user is not an administrator of this group.'], 400);
        }

        try {
            $isCreator = GroupChat::where('id', $groupChatId)
                                  ->where('creator_id', $userId)
                                  ->firstOrFail();
           
            if ($isCreator) {
                return response()->json(['error' => 'You can\'t demote the creator of this group'], 403);
            }
        } catch (Exception $e) {
            Log::info($e);
        }
    
        $member->is_admin = false;
        $member->save();
    
        return response()->json([
            'message' => 'The member has been successfully removed from their admin status.',
        ]);
        
    }catch(Exception $e){
        Log::info($e);
        return response()->json(['error' => $e->getMessage(),'message' => 'something went ron', ], 400);
    }
    } 

    /*
     * Create a group with its members and name.
     * example of body
     * {
     *      "creator_id": 1,
     *      "members": [1, 2, 3, 4], // at least the creator_id must always appear in this table
     *      "name": "My group name",
     *      "picture": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAQDAwMD... (base64-encoded image data)"
     * }
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    
    function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'creator_id' => 'required|exists:users,id',
            // 'members' => 'required|array|min:1',
            'name' => 'string|nullable',
            'picture' => 'image|mimes:jpeg,png,jpg,gif|max:2048|nullable',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Get validated input data
        $creatorId = $request->input('creator_id');
        $members = $request->input('members');
        $members = explode(',', $members);
        
        // json_decode($request->input('members')[0], true); 
        // $array = json_decode($string);
        $groupName = $request->input('name');
        $groupPicture = $request->file('picture');

        // Create the group
        $groupChat = new GroupChat();
        $groupChat->creator_id = $creatorId;
        $groupChat->admin_id = $creatorId;
        $groupChat->name = $groupName ?? 'Group';
        if ($groupPicture) {
            $ext = strtolower($groupPicture->getClientOriginalExtension());
            $directory = public_path().'/group_chat_pictures';
            $fileName = round(microtime(true)) .rand(2,50). '.' .$ext;
            $groupPicture->move($directory, $fileName);
            $groupChat->picture = ("public/group_chat_pictures/$fileName");
        }
        $groupChat->save();

        // Add members to the group
        $groupChatMembers = [];
        foreach ($members as $memberId) {
            $groupChatMember = new GroupChatMember();
            $groupChatMember->group_chat_id = $groupChat->id;
            $groupChatMember->user_id = 64;
            $groupChatMember->save();
        }
        
        // Check that the user we want to promote is not already an admin
         $groupMember = GroupChatMember::where('group_chat_id', $groupChat->id)
                                       ->where('user_id', $creatorId)
                                       ->first();
                        //  return count($groupMember);    
        // If the user is not already an admin, promote them
        if ($groupMember==null) {
            $groupChatMember = new GroupChatMember();
            $groupChatMember->group_chat_id = $groupChat->id;
            $groupChatMember->user_id = $creatorId;
            // $groupChatMember->is_admin = true;
            $groupChatMember->save();

           
        }
  $groupMember = GroupChatMember::where('group_chat_id', $groupChat->id)
                                            ->where('user_id', $creatorId)
                                            ->firstOrFail();
        // Promote the creator to admin
        $groupMember->is_admin = true;
        $groupMember->save();

        return response()->json(['group_chat' => $groupChat, 'message' => 'The group has been successfully created.'], 201);
    }

    function deleteMessage(Request $request, $groupChatId, $messageId) {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        $adminId = $request->input('admin_id');
        // User::where('id', $adminId)->firstOrFail();

        if (!GroupChatMember::where('group_chat_id', $groupChatId)
            ->where('user_id', $adminId)
            ->where('is_admin', true)
            ->exists()) {
                return response()->json(['error' => 'You are not authorized to delete this message.'], 403);
        }


        GroupChatMessage::where('group_chat_id', $groupChatId)
            ->where('id', $messageId)
            ->delete();

            return response()->json(['message' => 'The message has been successfully deleted.']);
    }

    function deleteGroup(Request $request, $groupChatId) {

        $validator = Validator::make($request->all(), [
            'creator_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $creatorId = User::where('id', $request->input('creator_id'))->firstOrFail();

        $groupChat = GroupChat::where('id', $groupChatId)
            ->where('creator_id', $creatorId->id)
            ->first();

        if (!$groupChat) {
            return response()->json(['error' => 'You are not authorized to delete this group.'], 403);
        }

        $groupChat->delete();

        return response()->json(['message' => 'The group has been successfully deleted.']);
    }

    function getMessages(Request $request, $groupChatId) {

        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $userId = request('userId');
        if (!GroupChat::where('id', $groupChatId)
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->exists()) {
                return response()->json(['error' => 'You are not a member of this group.'], 403);
        }

        $messages = GroupChatMessage::where('group_chat_id', $groupChatId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    function getUserGroups(Request $request, $userId) {
        if (!User::where('id', $userId)->exists()) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $groups = GroupChat::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();

        return response()->json(['groups' => $groups]);
    }


    public function leaveGroup($user_id, $group_chat_id){
        $group_chat = GroupChat::find($group_chat_id);
        $user = User::find($user_id);

        if (!$group_chat || !$user) {
            return response()->json(['message' => 'Invalid user or group chat ID'], 404);
        }

        $group_chat_member = GroupChatMember::where('group_chat_id', $group_chat_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$group_chat_member) {
            return response()->json(['message' => 'User is not a member of this group chat'], 404);
        }

        if ($group_chat_member->is_admin) {
            $group_chat_member->is_admin = false;
            $group_chat_member->save();
        } else if ($group_chat->creator_id == $user_id) {
            return response()->json(['message' => 'Creator cannot be removed from group chat'], 400);
        } else {
            $group_chat_member->delete();
        }

        return response()->json(['message' => 'User removed from group chat'], 200);
    }

}
