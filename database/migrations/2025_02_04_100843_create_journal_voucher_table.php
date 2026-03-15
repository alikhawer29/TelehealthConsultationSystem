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
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('ledger')->comment('party,general or walkin');
            $table->integer('account_id')->comment('party id,general id or walkin id');
            $table->text('narration')->nullable();
            $table->integer('currency_id')->comment('currency register id');
            $table->decimal('fc_amount', 15, 2);
            $table->decimal('rate', 15, 10);
            $table->decimal('lc_amount', 15, 2);
            $table->enum('sign', ['Debit', 'Credit']);
            $table->timestamps();
            $table->softDeletes();
            $table->integer('voucher_id');
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journal_voucher');
    }
};
