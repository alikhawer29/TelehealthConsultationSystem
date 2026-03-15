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
        // Schema::create('beneficiary_register', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('account', 255);
        //     $table->string('type', 100)->nullable();
        //     $table->string('name', 255)->nullable();
        //     $table->string('company', 255)->nullable();
        //     $table->text('address')->nullable();
        //     $table->mediumInteger('nationality')->unsigned()->nullable();
        //     $table->string('contact_no', 15)->nullable();
        //     $table->string('country_code', 5)->nullable();
        //     $table->string('bank_name', 255)->nullable();
        //     $table->string('bank_account_number', 50)->nullable();
        //     $table->string('swift_bic_code', 50)->nullable();
        //     $table->string('routing_number', 50)->nullable();
        //     $table->string('iban', 50)->nullable();
        //     $table->text('bank_address')->nullable();
        //     $table->mediumInteger('city')->unsigned()->nullable();
        //     $table->mediumInteger('country')->unsigned()->nullable();
        //     $table->string('corresponding_bank', 255)->nullable();
        //     $table->string('corresponding_bank_account_number', 50)->nullable();
        //     $table->string('corresponding_swift_bic_code', 50)->nullable();
        //     $table->string('corresponding_routing_number', 50)->nullable();
        //     $table->string('corresponding_iban', 50)->nullable();
        //     $table->bigInteger('purpose')->unsigned()->nullable();
        //     $table->string('branch', 255)->nullable();
        //     $table->string('ifsc_code', 50)->nullable();
        //     $table->integer('created_by')->unsigned();
        //     $table->integer('edited_by')->unsigned()->nullable();
        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->integer('parent_id')->unsigned();

        //     // Foreign key constraints
        //     $table->foreign('purpose')->references('id')->on('classification_master');
        //     $table->foreign('city')->references('id')->on('cities')->onDelete('cascade');
        //     $table->foreign('nationality')->references('id')->on('countries')->onDelete('cascade');
        //     // $table->foreign('country')->references('id')->on('country_register')->onDelete('cascade');
        //     $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        //     $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
        //     $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('beneficiary_register');
    }
};
