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
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // Reference to the users table
            $table->timestamp('login_time')->useCurrent(); // Login timestamp
            $table->ipAddress('ip_address')->nullable(); // IP Address of the user
            $table->string('status', 50)->nullable(); // Login status (success, failed, etc.)
            $table->timestamps(); // Default created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_login_logs');
    }
};
