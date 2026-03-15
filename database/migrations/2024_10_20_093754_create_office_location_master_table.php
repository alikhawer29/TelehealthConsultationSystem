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
        Schema::create('office_location_master', function (Blueprint $table) {
            $table->id();
            $table->string('office_location', 255);
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('parent_id');

            // Foreign keys
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
        Schema::dropIfExists('office_location_master');
    }
};
