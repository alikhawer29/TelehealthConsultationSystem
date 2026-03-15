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
        Schema::table('insurances', function (Blueprint $table) {
            $table->string('name_hash', 64)->nullable();
            $table->string('card_number_hash', 64)->nullable();
            $table->string('card_holder_name_hash', 64)->nullable();
            $table->string('status_hash', 64)->nullable();
            $table->string('reason_hash', 64)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurances', function (Blueprint $table) {
            //
        });
    }
};
