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
        Schema::table('vendors', function (Blueprint $table) {
            $table->after('id', function ($table) {
                $table->string('name')->nullable();
                $table->string('business_name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('country_code')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->boolean('status')->default(2)->comment = '1 = active, 2 = inactive';
                $table->softDeletes();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            //
        });
    }
};
