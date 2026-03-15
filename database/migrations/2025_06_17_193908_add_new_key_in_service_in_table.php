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
            $table->text('why_to_get_tested')->nullable(); // assuming it's a short alphanumeric string
            $table->text('specimen_type')->nullable(); // assuming it's a short alphanumeric string
            $table->text('preparation_needed')->nullable(); // assuming it's a short alphanumeric string
            $table->text('key_servies_included')->nullable(); // assuming it's a short alphanumeric string
            $table->text('what_to_expect')->nullable(); // assuming it's a short alphanumeric string
            $table->text('precautions')->nullable(); // assuming it's a short alphanumeric string
            $table->text('general_information')->nullable(); // assuming it's a short alphanumeric string
            $table->text('ingredients')->nullable(); // assuming it's a short alphanumeric string
            $table->text('preparations')->nullable(); // assuming it's a short alphanumeric string
            $table->text('administration_time')->nullable(); // assuming it's a short alphanumeric string
            $table->text('restriction')->nullable(); // assuming it's a short alphanumeric string
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
