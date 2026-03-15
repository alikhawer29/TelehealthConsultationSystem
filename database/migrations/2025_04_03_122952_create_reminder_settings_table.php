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
        Schema::create('reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['user', 'admin', 'doctor', 'physician', 'nurse'])->unique();
            $table->enum('reminder_time', ['none', 'at_time', '5_min', '10_min', '15_min', '30_min', '1_hour', '1_day', 'custom']);
            $table->integer('custom_time')->nullable(); // Only for custom reminders
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
        Schema::dropIfExists('reminder_settings');
    }
};
