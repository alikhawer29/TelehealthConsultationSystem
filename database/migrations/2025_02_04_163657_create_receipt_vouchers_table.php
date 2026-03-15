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
        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('ledger')->comment('party,general or walkin');
            $table->integer('account_id')->comment('party id,general id or walkin id');
            $table->text('narration')->nullable();
            $table->integer('received_from')->nullable();
            $table->enum('mode', ['Cash', 'Bank', 'PDC', 'Online']);
            $table->integer('mode_account_id')->nullable();
            $table->string('party_bank')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('due_date')->nullable();
            $table->integer('amount_account_id')->nullable();
            $table->decimal('amount', 15, 10);
            $table->enum('commission_type', ['Income', 'Expense']);
            $table->decimal('commission', 15, 10);
            $table->enum('vat_terms', ['Standard', 'Exempted', 'Zero Rate', 'Out of scope']);
            $table->decimal('vat_amount', 15, 10);
            $table->decimal('net_total', 15, 10);
            $table->text('comment')->nullable();
            $table->text('out_of_scope_reason')->nullable();
            $table->integer('voucher_id');
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
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
        Schema::dropIfExists('receipt_vouchers');
    }
};
