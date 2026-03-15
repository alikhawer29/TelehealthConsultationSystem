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
            $table->enum('professional', ['Physician', 'Nurse', 'Consultant'])->nullable()->after('role');
            $table->text('about')->nullable()->after('professional');
            $table->string('experience')->nullable()->after('about');
            $table->json('languages')->nullable()->after('session_type'); // Store multiple languages

        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['professional', 'about', 'experience', 'session_type']);
        });
    }
};
