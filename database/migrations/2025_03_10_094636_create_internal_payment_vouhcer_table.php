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
        Schema::create('internal_payment_voucher', function (Blueprint $table) {
            $table->id();
            $table->string('ledger');
            $table->integer('account_id'); // Foreign key for account
            $table->string('mode');
            $table->integer('mode_account_id'); // Foreign key for mode account
            $table->string('cheque_number')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('currency_id'); // Foreign key for currency
            $table->decimal('amount', 15, 2);
            $table->integer('cost_center_id'); // Foreign key for cost center
            $table->text('narration')->nullable();
            $table->string('modification_status')->default('active');
            $table->integer('voucher_id'); // Foreign key for voucher
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
        Schema::dropIfExists('internal_payment_vouhcer');
    }
};
