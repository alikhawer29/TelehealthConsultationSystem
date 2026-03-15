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

        Schema::create('walk_in_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 255);
            $table->string('company', 255)->nullable();
            $table->text('address')->nullable();
            $table->integer('city')->nullable(); // Change integer to string
            $table->string('designation', 100)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('mobile_country_code', 5)->nullable();
            $table->string('telephone_number', 15)->nullable();
            $table->string('telephone_country_code', 5)->nullable();
            $table->string('fax_number', 15)->nullable();
            $table->string('fax_country_code', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->integer('id_type')->nullable();
            $table->string('id_number', 50)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issue_place', 100)->nullable();
            $table->integer('nationality')->nullable(); // Change integer to string
            $table->enum('status', ['active', 'inactive'])->nullable();
            $table->string('vat_trn', 50)->nullable();
            $table->integer('vat_country')->nullable(); // Change integer to string
            $table->integer('vat_state')->nullable(); // Change integer to string
            $table->boolean('vat_exempted')->default(0);
            $table->integer('created_by'); // references users.id
            $table->integer('edited_by')->nullable(); // references users.id
            $table->timestamps(); // created_at and updated_at
            $table->integer('parent_id'); // references users.id
            $table->softDeletes(); // for deleted_at

            // Foreign key constraints
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
        Schema::dropIfExists('walk_in_customers');
    }
};
