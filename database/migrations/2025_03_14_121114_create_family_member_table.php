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
        Schema::create('family_member', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Foreign key for currency
            $table->integer('user_id'); // Foreign key for currency
            $table->string('emirates_id'); // Foreign key for currency
            $table->string('gender'); // Foreign key for currency
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('family_member');
    }
};
