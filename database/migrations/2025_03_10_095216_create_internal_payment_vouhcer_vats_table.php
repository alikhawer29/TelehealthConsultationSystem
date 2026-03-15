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
        Schema::create('internal_payment_voucher_vats', function (Blueprint $table) {
            $table->id();
            $table->integer('vouhcer_id'); // Foreign key for currency
            $table->string('vouhcer_type');
            $table->string('ledger');
            $table->integer('debit_account_id');
            $table->text('narration')->nullable();
            $table->integer('currency_id'); // Foreign key for currency
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('vat_terms')->nullable();
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->decimal('total', 15, 2);
            $table->text('out_of_scope_reason')->nullable();
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
        Schema::dropIfExists('internal_payment_vouhcer_vats');
    }
};
