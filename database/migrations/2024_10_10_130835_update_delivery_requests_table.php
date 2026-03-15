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
        Schema::table('delivery_requests', function (Blueprint $table) {
            // Change status column to string
            $table->string('status')->default('Unassigned')->change();

            // Make driver_id nullable
            $table->integer('driver_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            // Revert changes made in the up() method
            $table->enum('status', [
                'Unassigned',
                'Assigned',
                'Delivered',
                'Cancelled',
                'In-Transit'
            ])->default('Unassigned')->change(); // Change back to enum if needed

            $table->integer('driver_id')->nullable(false)->change(); // Make driver_id non-nullable
        });
    }
};
