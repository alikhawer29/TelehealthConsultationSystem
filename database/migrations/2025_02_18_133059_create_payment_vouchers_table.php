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
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('ledger');
            $table->integer('account_id'); // Foreign key for account
            $table->string('paid_to');
            $table->integer('paid_to_id'); // Foreign key for paid_to
            $table->string('mode');
            $table->integer('mode_account_id'); // Foreign key for mode account
            $table->string('cheque_number')->nullable();
            $table->date('due_date')->nullable();
            $table->text('narration')->nullable();
            $table->decimal('amount', 15, 2);
            $table->integer('currency_id'); // Foreign key for currency
            $table->string('commission_type')->nullable();
            $table->decimal('commission', 15, 2)->nullable();
            $table->decimal('commission_amount', 15, 2)->nullable();
            $table->string('vat_terms')->nullable();
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->decimal('net_total', 15, 2);
            $table->text('comment')->nullable();
            $table->text('out_of_scope_reason')->nullable();
            $table->string('modification_status')->default('pending');
            $table->integer('voucher_id')->nullable(); // Foreign key for voucher
            $table->integer('branch_id'); // Foreign key for branch
            $table->integer('business_id'); // Foreign key for business
            $table->integer('created_by'); // Foreign key for user who created
            $table->integer('edited_by')->nullable(); // Foreign key for user who edited
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
        Schema::dropIfExists('payment_vouchers');
    }
};
