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
        // Schema::table('users', function (Blueprint $table) {
        //     $table->renameColumn('first_name', 'business_name');
        //     $table->renameColumn('last_name', 'user_name');
        //     $table->dropColumn('office_phone');
        //     $table->dropColumn('office_country_code');
        //     $table->dropColumn('billing_address');
        //     $table->dropColumn('city');
        //     $table->dropColumn('state');
        //     $table->dropColumn('country');
        //     $table->dropColumn('zip_code');
        //     $table->dropColumn('verification_token');
        //     $table->dropColumn('is_verified');
        //     $table->dropColumn('independent_contractor');
        //     $table->integer('parent_id')->unsigned()->nullable(); // Parent user/company
        //     $table->string('user_id', 255)->unique()->nullable(false); // unique user ID with prefix
        //     $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
