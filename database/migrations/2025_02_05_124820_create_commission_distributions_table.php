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
        Schema::create('commission_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_commission_id')->constrained('special_commissions')->onDelete('cascade');
            $table->string('ledger')->comment('party,general or walkin');
            $table->integer('credit_account_id')->comment('party id,general id or walkin id');
            $table->text('narration')->nullable();
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 15, 2);
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
        Schema::dropIfExists('commission_distributions');
    }
};
