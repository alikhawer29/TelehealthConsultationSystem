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
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->enum('slot_type', ['doctor', 'homecare_service', 'lab_service']);
            $table->morphs('reference'); // Creates `reference_id` (bigint) and `reference_type` (string)
            $table->string('day');
            $table->string('day_name');
            $table->integer('index');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('status')->default(1);
            $table->enum('booking_status', ['upcoming', 'inprogress', 'cancelled', 'completed'])->default('upcoming');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('slots');
    }
};
