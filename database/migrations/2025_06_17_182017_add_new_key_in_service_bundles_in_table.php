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
        Schema::table('service_bundles', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->text('why_to_get_tested')->nullable(); // assuming it's a short alphanumeric string
            $table->text('specimen_type')->nullable(); // assuming it's a short alphanumeric string
            $table->text('preparation_needed')->nullable(); // assuming it's a short alphanumeric string
            $table->text('parameter_list')->nullable(); // assuming it's a short alphanumeric string
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_bundles_in', function (Blueprint $table) {
            //
        });
    }
};
