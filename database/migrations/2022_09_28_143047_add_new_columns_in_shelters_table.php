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
        Schema::table('shelters', function (Blueprint $table) {
            $table->after('id',function($table){
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('phone')->nullable();
                $table->float('lat')->nullable();
                $table->float('lng')->nullable();
                $table->string('address')->nullable();
                $table->string('description')->nullable();
                $table->boolean('status')->nullable()->default(true);

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
        Schema::table('shelters', function (Blueprint $table) {
            $table->dropColumn(['name','email','password','status','phone','lat','lng','address','description']);
        });
    }
};
