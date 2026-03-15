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
        Schema::create('company_details', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->integer('company_id'); // Foreign key referencing users.id
            $table->string('company_name');
            $table->string('title');
            $table->string('federal_tax_eid');
            $table->text('terms')->nullable();
            $table->string('terminals')->nullable();
            $table->string('building_no')->nullable();
            $table->timestamps();
            $table->softDeletes(); // To handle 'deleted_at'

            // Foreign key constraint
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
        Schema::dropIfExists('company_details');
    }
};
