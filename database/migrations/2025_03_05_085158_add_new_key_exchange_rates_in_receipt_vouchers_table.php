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
        Schema::table('receipt_vouchers', function (Blueprint $table) {
            $table->string('exchange_rates')->after('amount_account_id')->nullable()->comment('Exchange rates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipt_vouchers', function (Blueprint $table) {
            //
        });
    }
};
