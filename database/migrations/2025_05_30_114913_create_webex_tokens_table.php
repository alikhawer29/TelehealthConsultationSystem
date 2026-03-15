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
        Schema::create('webex_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('doctor_id')->unique(); // Reference to users or doctors table
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamp('expires_at'); // Token expiry
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
        Schema::dropIfExists('webex_tokens');
    }
};
