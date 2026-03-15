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
        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->integer('supplier_id'); // Foreign key referencing users.id
            $table->integer('quotation_id'); // Foreign key referencing quotations.id
            $table->integer('driver_id'); // Foreign key referencing users.id
            $table->integer('company_id'); // Foreign key referencing users.id
            $table->string('status'); // Delivery request status
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // To handle 'deleted_at'

            // Foreign key constraints
            $table->foreign('supplier_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_requests');
    }
};
