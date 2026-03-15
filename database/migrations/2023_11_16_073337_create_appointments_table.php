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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->boolean('have_video')->default(2)->comment = '1 = true, 2 = false';
            $table->string('video_link')->nullable();
            $table->boolean('is_video_record')->default(2)->comment = '1 = true, 2 = false';
            $table->text('court_address')->nullable();
            $table->string('visit_charges');
            $table->string('phone');
            $table->text('note')->nullable();
            $table->string('slot_id');
            $table->string('type')->comment = 'on-site, online';
            $table->morphs('userable');
            $table->morphs('bookable');
            $table->morphs('packageable');
            $table->string('request_date')->nullable();
            $table->string('request_time')->nullable();
            $table->string('status')->comment = 'booking-status';
            $table->string('appointment_status')->comment = 'appointment-status';
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
