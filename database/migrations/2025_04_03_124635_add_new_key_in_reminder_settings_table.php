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
        Schema::table('reminder_settings', function (Blueprint $table) {
            $table->integer('reference_id')->nullable()->after('custom_time'); // Only for custom reminders
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reminder_settings', function (Blueprint $table) {
            //
        });
    }
};
