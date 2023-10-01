<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::create('group_chats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('picture')->nullable();
            $table->unsignedBigInteger('admin_id');
            $table->foreign('admin_id')->references('id')->on('users');
            $table->unsignedBigInteger('creator_id');
            $table->foreign('creator_id')->references('id')->on('users');
            $table->timestamps();
        });
        
        Schema::create('group_chat_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_chat_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('group_chat_id')->references('id')->on('group_chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
        
        Schema::create('group_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_chat_id');
            $table->unsignedBigInteger('sender_id');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('content');
            $table->string('type');
            $table->string('filename')->nullable();
            $table->string('filepath')->nullable();
            $table->timestamps();
        });
        
        Schema::create('message_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('message_id')->references('id')->on('group_chat_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::dropIfExists('chats');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('group_chats');
        Schema::dropIfExists('group_chat_members');
        Schema::dropIfExists('group_chat_messages');
        Schema::dropIfExists('message_statuses');
    }
};
