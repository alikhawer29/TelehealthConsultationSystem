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
        Schema::create('remittance_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currency_register')->onDelete('cascade');
            $table->foreignId('ag_fcy_id')->constrained('currency_register')->onDelete('cascade');
            $table->decimal('buy_rate', 15, 6);
            $table->decimal('buy_from', 15, 6)->nullable();
            $table->decimal('buy_upto', 15, 6)->nullable();
            $table->decimal('sell_rate', 15, 6);
            $table->decimal('sell_from', 15, 6)->nullable();
            $table->decimal('sell_upto', 15, 6)->nullable();
            $table->enum('action', ['lock', 'unlock'])->default('unlock');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remittance_rates');
    }
};
