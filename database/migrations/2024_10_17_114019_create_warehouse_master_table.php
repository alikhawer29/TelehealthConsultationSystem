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
        Schema::create('warehouse_master', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('code'); // Code for the warehouse
            $table->string('name'); // Name of the warehouse
            $table->integer('created_by'); // Foreign key reference to users
            $table->integer('edited_by')->nullable(); // Foreign key reference to users
            $table->integer('parent_id'); // Foreign key reference to users
            $table->timestamps(); // Created at and updated at timestamps
            $table->softDeletes(); // Soft delete column

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
        Schema::dropIfExists('warehouse_master');
    }
};
