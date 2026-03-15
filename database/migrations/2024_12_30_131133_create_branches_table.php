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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');  // Make sure user_id is unsigned
            $table->string('name');
            $table->string('address');
            $table->string('manager');
            $table->string('supervisor');
            $table->string('base_currency');
            $table->enum('status', ['Blocked', 'Unblocked'])->default('Unblocked');
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
        Schema::dropIfExists('branches');
    }
};
