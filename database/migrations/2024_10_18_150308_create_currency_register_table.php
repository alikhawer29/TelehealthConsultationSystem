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
        Schema::create('currency_register', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 10);
            $table->string('currency_name', 50)->nullable();
            $table->string('rate_type', 50)->nullable();
            $table->string('currency_type', 50)->nullable();
            $table->decimal('rate_variation', 10, 4)->nullable();
            $table->string('group', 50)->nullable();
            $table->boolean('allow_online_rate')->default(false);
            $table->boolean('allow_auto_pairing')->default(false);
            $table->boolean('allow_second_preference')->default(false);
            $table->boolean('restrict_pair')->default(false);
            $table->string('special_rate_currency', 50)->nullable();
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->integer('parent_id');
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('currency_register');
    }
};
