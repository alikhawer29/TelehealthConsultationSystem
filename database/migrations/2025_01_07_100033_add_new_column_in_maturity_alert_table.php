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
        Schema::table('maturity_alert', function (Blueprint $table) {
            $table->string('status')->nullable()->after('levels');
            $table->string('levels')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maturity_alert', function (Blueprint $table) {
            //
        });
    }
};
