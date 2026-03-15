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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_type', 50);
            $table->string('account_name', 255)->nullable();
            $table->unsignedBigInteger('parent_account_id')->nullable();
            $table->text('description')->nullable();
            $table->string('account_code', 50)->nullable();
            $table->integer('level')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('created_by');
            $table->integer('edited_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('parent_id'); // Foreign key for user management

            // Foreign Key Constraints
            $table->foreign('parent_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
