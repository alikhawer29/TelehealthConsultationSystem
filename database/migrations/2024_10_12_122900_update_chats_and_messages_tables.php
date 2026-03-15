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
        // Remove is_open column from messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('is_open');
        });

        // Add status column to chats table
        Schema::table('chats', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('purchase_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Rollback: Add is_open column back to messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_open')->default(false)->after('sender_id'); // Adjust position as needed
        });

        // Rollback: Remove status column from chats table
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
