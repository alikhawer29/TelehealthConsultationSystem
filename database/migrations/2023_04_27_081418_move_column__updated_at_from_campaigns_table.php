<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN updated_at timestamp AFTER status");
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN created_at timestamp AFTER status");
        DB::statement("ALTER TABLE commission MODIFY COLUMN created_at timestamp AFTER rate_type");
        DB::statement("ALTER TABLE commission MODIFY COLUMN updated_at timestamp AFTER rate_type");
        DB::statement("ALTER TABLE orders MODIFY COLUMN created_at timestamp AFTER order_owner_id");
        DB::statement("ALTER TABLE orders MODIFY COLUMN updated_at timestamp AFTER order_owner_id");
        DB::statement("ALTER TABLE payments MODIFY COLUMN created_at timestamp AFTER split_payment_data");
        DB::statement("ALTER TABLE payments MODIFY COLUMN updated_at timestamp AFTER split_payment_data");
        DB::statement("ALTER TABLE shelters MODIFY COLUMN created_at timestamp AFTER account_holder_name");
        DB::statement("ALTER TABLE shelters MODIFY COLUMN updated_at timestamp AFTER account_holder_name");
        DB::statement("ALTER TABLE shelters MODIFY COLUMN deleted_at timestamp AFTER account_holder_name");
        DB::statement("ALTER TABLE users MODIFY COLUMN created_at timestamp AFTER stripe_id");
        DB::statement("ALTER TABLE users MODIFY COLUMN updated_at timestamp AFTER stripe_id");
        DB::statement("ALTER TABLE users MODIFY COLUMN deleted_at timestamp AFTER stripe_id");
        DB::statement("ALTER TABLE vendors MODIFY COLUMN created_at timestamp AFTER account_holder_name");
        DB::statement("ALTER TABLE vendors MODIFY COLUMN updated_at timestamp AFTER account_holder_name");
        DB::statement("ALTER TABLE vendors MODIFY COLUMN deleted_at timestamp AFTER account_holder_name");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            //
        });
    }
};
