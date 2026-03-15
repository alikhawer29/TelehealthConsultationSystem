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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_hash', 64)->nullable()->index();
            $table->string('first_name_hash', 64)->nullable()->index();
            $table->string('last_name_hash', 64)->nullable()->index();
            $table->string('phone_number_hash', 64)->nullable()->index();
            $table->string('country_code_hash', 64)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
