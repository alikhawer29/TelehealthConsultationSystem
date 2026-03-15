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
        Schema::create('commission_master', function (Blueprint $table) {
            $table->id();
            $table->string('account_type')->comment('party, walkin, or general');
            $table->integer('account');
            $table->string('commission_type')->comment('income or expense');
            $table->decimal('receipt_percentage', 10, 2)->nullable();
            $table->decimal('payment_percentage', 10, 2)->nullable();
            $table->decimal('tmn_buy_remittance_percentage', 10, 2)->nullable();
            $table->decimal('tmn_sell_remittance_percentage', 10, 2)->nullable();
            $table->decimal('currency_transfer_request_percentage', 10, 2)->nullable();
            $table->decimal('outward_remittance_percentage', 10, 2)->nullable();
            $table->decimal('currency_buy_sell_percentage', 10, 2)->nullable();
            $table->decimal('inward_remittance_percentage', 10, 2)->nullable();
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commission_master');
    }
};
