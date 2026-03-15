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
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->dropColumn('details');
            $table->dropColumn('for');
            $table->dropColumn('to');
            $table->dropColumn('from');
            $table->renameColumn('amount', 'price_monthly');
            $table->string('price_yearly');
            $table->string('branches');
            $table->integer('no_of_users');
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
