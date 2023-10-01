<?php

use App\Models\GroupChatMember;
use App\Models\GroupChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

const USER_CONTROLLER = App\Http\Controllers\User\UserController::class;
const CHAT_LIST_API_CONTROLLER = App\Http\Controllers\User\ChatListController::class;
const CHAT_MESSAGE_API_CONTROLLER = App\Http\Controllers\User\ChatMessageController::class;

const GROUP_CHAT_API_CONTROLLER = App\Http\Controllers\group\GroupChatController::class;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'group'], function() {
    Route::group(['prefix' => 'message'], function() {
        Route::post('/{group_chat_id}/messages/{message_id}/status', [GROUP_CHAT_API_CONTROLLER, 'saveMessageStatus']);
        Route::get('/group_chats/{id}/messages', [GROUP_CHAT_API_CONTROLLER, 'getStatusGroupMessages']);
        Route::post('/{group_id}/messages', [GROUP_CHAT_API_CONTROLLER, 'sendMessage']);        
        Route::get('/{groupChatId}/messages', [GROUP_CHAT_API_CONTROLLER, 'getMessages']);
        Route::delete('/{groupChatId}/messages/{messageId}', [GROUP_CHAT_API_CONTROLLER, 'deleteMessage']);
    });
    Route::delete('/leavegroup/{user_id}/{group_chat_id}', [GROUP_CHAT_API_CONTROLLER, 'leaveGroup']);
    Route::post('/{groupChatId}/add', [GROUP_CHAT_API_CONTROLLER, 'addGroupMember']);
    Route::post('/{group_id}/add-admin/{user_id}', [GROUP_CHAT_API_CONTROLLER, 'addGroupAdmin']);
    Route::delete('/{group_id}/member/{member_id}', [GROUP_CHAT_API_CONTROLLER, 'removeGroupMember']);    
    Route::put('/{groupChatId}/demote-admin/{adminId}', [App\Http\Controllers\group\GroupChatController::class, 'removeGroupAdmin']);
    Route::post('/create', [GROUP_CHAT_API_CONTROLLER, 'createGroup']);    
    Route::delete('/{groupChatId}', [GROUP_CHAT_API_CONTROLLER, 'deleteGroup']);
    Route::get('/users/{userId}/groups', [GROUP_CHAT_API_CONTROLLER, 'getUserGroups']);
});


Route::group(['prefix' => 'user'], function() {
    // USER_CONTROLLER
    Route::post('/update-order-files', [USER_CONTROLLER, 'updateOrderFiles']);
    // CHAT_LIST_API_CONTROLLER
    Route::get('/get-chat-list/{id}', [CHAT_LIST_API_CONTROLLER, 'getChatList']);
    Route::post('/send-message', [CHAT_LIST_API_CONTROLLER, 'sendMessages']);
    Route::post('/get-messages', [CHAT_MESSAGE_API_CONTROLLER, 'getMessages']);
    
    Route::post('/send-message-to-all-users', [CHAT_LIST_API_CONTROLLER, 'sendMessagesToAllUsers']); //send Messages To All Users
    Route::post('/send-message-to-users', [CHAT_LIST_API_CONTROLLER, 'sendMessagesToUsers']); //send Messages To Users
});
