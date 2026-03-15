<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->integer('chat_id'); // Foreign key for chat
            $table->integer('sender_id'); // Foreign key for sender
            $table->text('message'); // Message content
            $table->boolean('is_read')->default(false); // Read status
            $table->timestamps(); // created_at and updated_at

            // Foreign key constraints
            // $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
