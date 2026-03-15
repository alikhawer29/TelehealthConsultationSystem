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
        Schema::create('cheque_register', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_number')->nullable(); // Cheque number
            $table->integer('bank')->nullable(); // Bank name
            $table->string('transaction_no')->nullable(); // Transaction number
            $table->integer('issued_to')->nullable(); // Issued to
            $table->decimal('amount', 15, 2)->nullable(); // Amount with precision
            $table->string('status')->nullable(); // Status (e.g., pending, approved)
            $table->string('reference_no')->nullable(); // Reference number
            $table->integer('count')->nullable(); // Count field
            $table->integer('starting_no')->nullable(); // Count field

            $table->integer('business_id'); // Foreign key for business
            $table->integer('branch_id'); // Foreign key for branch
            $table->timestamps(); // Created at and updated at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cheque_register');
    }
};
