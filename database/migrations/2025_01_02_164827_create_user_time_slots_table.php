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
        Schema::create('user_time_slots', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID for each time slot
            $table->integer('user_id'); // Foreign key to users table
            $table->string('day'); // Day (e.g., 'Monday', 'Tuesday', etc.)
            $table->time('from'); // Start time
            $table->time('to'); // End time
            $table->timestamps(); // Timestamps for created_at and updated_at

            // Add foreign key constraint for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_time_slots');
    }
};
