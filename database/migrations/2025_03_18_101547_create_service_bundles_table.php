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
        Schema::create('service_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('bundle_name', 255);
            $table->text('descritpion');
            $table->decimal('price', 10, 2);
            $table->tinyInteger('status')->default(1); // Active (1), Inactive (0)
            $table->softDeletes();
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
        Schema::dropIfExists('service_bundles');
    }
};
