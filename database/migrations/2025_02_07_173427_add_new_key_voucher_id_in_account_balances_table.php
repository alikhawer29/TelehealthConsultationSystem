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
        Schema::table('account_balances', function (Blueprint $table) {
            $table->integer('voucher_id');
        });

        Schema::table('trial_balance', function (Blueprint $table) {
            $table->integer('voucher_id');
        });
        Schema::table('balance_sheet', function (Blueprint $table) {
            $table->integer('voucher_id');
        });
        Schema::table('profit_loss_statement', function (Blueprint $table) {
            $table->integer('voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_balances', function (Blueprint $table) {
            //
        });
    }
};
