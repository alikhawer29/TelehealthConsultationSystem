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
        Schema::create('special_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no')->unique();
            $table->integer('voucher_id');
            $table->date('date');
            $table->enum('commission_type', ['income', 'expense']);
            $table->string('account_type')->comment('party,general or walkin');
            $table->foreignId('account_id')->comment('party id, general id or walkin id');
            $table->foreignId('amount_type')->constrained('currency_register')->onDelete('cascade')->comment('currency_register_id');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->decimal('commission', 15, 2);
            $table->decimal('total_commission', 15, 2);
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
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
        Schema::dropIfExists('special_commissions');
    }
};
