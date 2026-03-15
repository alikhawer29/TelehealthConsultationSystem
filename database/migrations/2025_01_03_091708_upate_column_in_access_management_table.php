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
        Schema::table('access_management', function (Blueprint $table) {
            $table->renameColumn('user_id', 'business_id');
            $table->renameColumn('feature', 'parent');
            $table->string('module');
            $table->string('permission');
            $table->boolean('granted');
            $table->dropColumn('access_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('access_management', function (Blueprint $table) {
            //
        });
    }
};
