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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('business_name', 'first_name');
            $table->renameColumn('user_name', 'last_name');
            $table->dropColumn('parent_id');
            $table->dropColumn('user_id');
            $table->dropColumn('selected_branch');
            $table->dropColumn('apply_time_restriction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
