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
        Schema::create('query_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('report_type')->comment('shop,adoption,shelter');
            $table->string('email');
            $table->integer('ad_id');
            $table->longText('reason')->nullable();
            $table->boolean('status')->default(0)->comment('1 is block, 0 is active');
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
        Schema::dropIfExists('query_reports');
    }
};
