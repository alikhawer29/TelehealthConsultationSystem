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
        Schema::table('family_member', function (Blueprint $table) {
            $table->string('name_hash', 64)->nullable()->index();
            $table->string('emirates_id_hash', 64)->nullable()->index();
            $table->string('gender_hash', 64)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('family_member', function (Blueprint $table) {
            //
        });
    }
};
