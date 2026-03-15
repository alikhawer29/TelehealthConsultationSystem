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
        Schema::table('service', function (Blueprint $table) {
            $table->text('parameters_included')->nullable()->after('about');
            $table->text('fasting_requirments')->nullable()->after('precautions');
            $table->text('turnaround_time')->nullable()->after('fasting_requirments');
            $table->text('when_to_get_tested')->nullable()->after('turnaround_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
};
