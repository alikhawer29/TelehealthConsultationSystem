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
        Schema::create('site_information', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('website_link')->nullable();
            $table->json('whatsapp_numbers')->nullable();  // Store multiple numbers
            $table->json('landline_numbers')->nullable(); // Store multiple landlines
            $table->json('social_media')->nullable(); // Store multiple social media links
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
        Schema::dropIfExists('site_information');
    }
};
