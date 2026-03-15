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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->after('ad_id',function($table){
                $table->morphs('owner');
                $table->string('cost')->nullable();
                $table->timestamp('start_date')->nullable();
            });
            $table->string('status')->nullable()->default('pending')->comment('pending,approved,rejected,cancelled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropMorphs('owner');
            $table->dropColumn(['cost','start_date','status']);
        });
    }
};
