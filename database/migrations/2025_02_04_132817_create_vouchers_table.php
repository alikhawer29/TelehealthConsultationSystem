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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_type');
            $table->string('voucher_no');
            $table->string('total_debit')->defalut(0);
            $table->string('total_credit')->defalut(0);
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('vouchers');
    }
};
