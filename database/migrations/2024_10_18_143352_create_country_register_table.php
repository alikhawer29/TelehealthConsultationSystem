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
        Schema::create('country_register', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->string('country', 255);
            $table->date('creation_date');
            $table->integer('created_by'); // references users.id
            $table->integer('edited_by')->nullable(); // references users.id
            $table->timestamps();
            $table->integer('parent_id'); // references users.id
            $table->softDeletes();

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
        Schema::dropIfExists('country_register');
    }
};
