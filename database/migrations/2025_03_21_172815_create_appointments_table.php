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
        // Schema::create('appointments', function (Blueprint $table) {
        //     $table->id();
        //     $table->integer('booking_id');
        //     $table->integer('slot_id');
        //     $table->integer('user_id'); // User who booked the appointment
        //     $table->enum('service_type', ['doctor', 'homecare', 'lab']);
        //     $table->enum('session_type', ['chat', 'call', 'video_call']);

        //     // Polymorphic relation
        //     $table->morphs('bookable'); // Creates bookable_id and bookable_type

        //     $table->enum('status', ['scheduled', 'cancelled', 'requested'])->default('scheduled');
        //     $table->enum('appointment_status', ['upcoming', 'inprogress', 'completed','missed'])->default('upcoming');

        //     $table->date('request_date')->nullable();
        //     $table->time('request_start_time')->nullable();
        //     $table->time('request_end_time')->nullable();

        //     $table->date('appointment_date')->nullable();
        //     $table->time('appointment_start_time')->nullable();
        //     $table->time('appointment_end_time')->nullable();

        //     $table->string('session_code')->nullable();
        //     $table->boolean('is_live')->default(false);
        //     $table->decimal('amount', 10, 2)->nullable();

        //     $table->softDeletes(); // Adds deleted_at column for soft deletion
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('appointments');
    }
};
