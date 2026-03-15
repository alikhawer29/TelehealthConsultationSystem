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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->integer('currency_id')->comment('exchange rate');
            $table->decimal('exchange_rate', 10, 4);
            $table->decimal('converted_amount', 15, 2);
            $table->integer('voucher_id');
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by');
            $table->timestamps();
        });

        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->decimal('debit_total', 15, 2)->default(0);
            $table->decimal('credit_total', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by');
            $table->timestamps();
        });

        Schema::create('trial_balance', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by');
            $table->timestamps();
        });

        Schema::create('balance_sheet', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->decimal('balance', 15, 2);
            $table->string('type'); // Asset, Liability, Equity
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by');
            $table->timestamps();
        });

        Schema::create('profit_loss_statement', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->decimal('amount', 15, 2);
            $table->string('type'); // Income or Expense
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by');
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
        Schema::dropIfExists('ledger_entries');
    }
};
