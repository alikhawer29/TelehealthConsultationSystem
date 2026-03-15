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
        Schema::create('cb_classification_master', function (Blueprint $table) {
            $table->id();
            $table->string('classification', 255);
            $table->text('description')->nullable();
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->integer('parent_id');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key references
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
        Schema::dropIfExists('cb_classification_master');
    }
};
