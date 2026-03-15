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
        Schema::create('accounts_permission', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id');
            $table->integer('employee_id');
            $table->integer('chart_of_account_id');
            $table->boolean('granted');
            $table->timestamps();
            // Add foreign key constraint for user_id
            $table->foreign('business_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts_permission');
    }
};
