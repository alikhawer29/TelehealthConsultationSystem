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
        Schema::create('party_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 50);
            $table->string('account_title', 255)->nullable();
            $table->string('rtl_title', 255)->nullable();
            $table->string('classification', 255)->nullable();
            $table->unsignedBigInteger('central_bank_group')->nullable();
            $table->string('debit_posting_account', 50)->nullable();
            $table->string('credit_posting_account', 50)->nullable();
            $table->enum('status', ['active', 'inactive']);
            $table->boolean('offline_iwt_entry')->default(0);
            $table->boolean('money_service_agent')->default(0);
            $table->string('office', 255)->nullable();
            $table->decimal('debit_limit', 10, 2)->nullable();
            $table->decimal('credit_limit', 10, 2)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('telephone_number', 15)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('fax', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('mobile_country_code', 5)->nullable();
            $table->integer('nationality')->nullable();
            $table->string('entity', 50)->nullable();
            $table->integer('id_type')->nullable();
            $table->string('id_number', 50)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('valid_upto')->nullable();
            $table->string('issue_place', 100)->nullable();
            $table->string('vat_trn', 50)->nullable();
            $table->integer('vat_country')->nullable();
            $table->integer('vat_state')->nullable();
            $table->boolean('vat_exempted')->default(0);
            $table->decimal('outward_tt_commission', 10, 2)->nullable();
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->integer('parent_id');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('central_bank_group')->references('id')->on('cb_classification_master')->onDelete('cascade');
            $table->foreign('vat_country')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('nationality')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('vat_state')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('id_type')->references('id')->on('classification_master')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('party_ledgers');
    }
};
