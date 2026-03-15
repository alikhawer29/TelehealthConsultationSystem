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
        Schema::table('special_commissions', function (Blueprint $table) {
            $table->enum('modification_status', ['active', 'edited', 'deleted'])->default('active');
        });

        Schema::table('commission_distributions', function (Blueprint $table) {
            $table->enum('modification_status', ['active', 'edited', 'deleted'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            //
        });
    }
};
