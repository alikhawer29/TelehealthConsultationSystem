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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->integer('voucher_id'); // Foreign key for payment_vouchers
            $table->string('voucher_type');
            $table->string('cardholder_name')->nullable();
            $table->string('card_number')->nullable();
            $table->string('cvv_number')->nullable();
            $table->date('validity_date')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('swift_bic_code')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('iban')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_details');
    }
};
