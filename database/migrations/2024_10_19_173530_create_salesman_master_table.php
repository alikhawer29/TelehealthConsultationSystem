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
        Schema::create('salesman_master', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 255)->nullable();
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->integer('parent_id');
            $table->timestamps();
            $table->softDeletes(); // For deleted_at column

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('edited_by')->references('id')->on('users');
            $table->foreign('parent_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesman_master');
    }
};
