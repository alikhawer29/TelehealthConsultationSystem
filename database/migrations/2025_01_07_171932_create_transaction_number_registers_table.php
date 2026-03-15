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
        Schema::create('transaction_number_registers', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('transaction_type'); // Journal Voucher or others
            $table->string('prefix')->default('JV'); // Default Prefix
            $table->integer('starting_no')->default(1); // Starting number, default is 1
            $table->string('next_transaction_no')->nullable(); // Next Transaction Number, can be null initially
            $table->integer('transaction_number_limit')->default(1000); // Limit for transaction numbers
            $table->boolean('auto_generate_transaction_number')->default(true); // Auto-generate flag
            $table->integer('user_id'); // Starting number, default is 1
            $table->integer('branch_id')->default(1); // Starting number, default is 1
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
        Schema::dropIfExists('transaction_number_registers');
    }
};
