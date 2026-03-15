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
        Schema::create('cost_register_center', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('type')->comment('detail or group');
            $table->string('group');
            $table->text('description')->nullable();
            $table->boolean('default');
            $table->integer('branch_id');
            $table->integer('business_id');
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_register_center');
    }
};
