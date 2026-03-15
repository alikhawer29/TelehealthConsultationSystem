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
            $table->dropColumn('description');
            $table->text('about')->nullable(); // assuming it's a short alphanumeric string
            $table->text('conditions_to_treat')->nullable(); // assuming it's a short alphanumeric string
            $table->text('what_to_expect_during_the_sessions')->nullable(); // assuming it's a short alphanumeric string
            $table->text('preparations_and_precautions')->nullable(); // assuming it's a short alphanumeric string
            $table->text('who_should_consider_this_service')->nullable(); // assuming it's a short alphanumeric string
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_in', function (Blueprint $table) {
            //
        });
    }
};
