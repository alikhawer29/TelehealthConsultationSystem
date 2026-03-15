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
        Schema::table('branches', function (Blueprint $table) {
            // Adding nullable foreign keys for Posting Accounts
            $table->integer('account_payable')->nullable()->after('status');
            $table->integer('account_receivable')->nullable();
            $table->integer('pdcr_account')->nullable();
            $table->integer('pdcp_account')->nullable();
            $table->integer('walk_in_customer_account')->nullable();
            $table->integer('suspense_account')->nullable();
            $table->integer('invalid_payment_order_account')->nullable();
            $table->integer('foreign_currency_remittance_account')->nullable();
            $table->integer('commission_income_account')->nullable();
            $table->integer('commission_expense_account')->nullable();
            $table->integer('discount_account')->nullable();
            $table->integer('iwt_receivable_account')->nullable();
            $table->integer('vat_input_account')->nullable();
            $table->integer('vat_output_account')->nullable();
            $table->integer('remittance_income_account')->nullable();
            $table->integer('counter_income_account')->nullable();
            $table->integer('vat_absorb_expense_account')->nullable();
            $table->integer('cost_of_sale_account')->nullable();
            $table->integer('stock_in_hand_account')->nullable();
            $table->integer('depreciation_expense_account')->nullable();
            $table->integer('gain_or_loss_on_sale_account')->nullable();
            $table->integer('write_off_account')->nullable();

            // Adding system dates
            $table->date('opening_date')->nullable();
            $table->date('closed_upto_date')->nullable();
            $table->date('accept_data_upto_date')->nullable();

            // Adding dashboard parameters
            $table->unsignedInteger('startup_alert_period')->nullable();
            $table->unsignedInteger('currency_rate_trend')->nullable();
            $table->unsignedInteger('dashboard_comparison_period')->nullable();

            // Adding central bank limits
            $table->unsignedInteger('inward_payment_order_limit')->nullable();
            $table->unsignedInteger('outward_remittance_limit')->nullable();
            $table->unsignedInteger('counter_transaction_limit')->nullable();
            $table->unsignedInteger('cash_limit')->nullable();
            $table->unsignedInteger('cash_bank_pay_limit')->nullable();
            $table->unsignedInteger('monthly_transaction_limit')->nullable();
            $table->unsignedInteger('counter_commission_limit')->nullable();

            // Adding VAT parameters
            $table->decimal('vat_trn', 10, 2)->nullable();
            $table->integer('vat_country')->nullable();
            $table->string('default_city')->nullable();
            $table->text('cities')->nullable();
            $table->string('vat_type')->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();

            // Adding miscellaneous parameters
            $table->boolean('disable_party_id_validation')->default(false);
            $table->boolean('disable_beneficiary_checking')->default(false);
            $table->boolean('enable_personalized_marking')->default(false);
            $table->boolean('show_agent_commission_in_cbs')->default(false);
            $table->boolean('show_agent_commission_in_fsn')->default(false);
            $table->boolean('show_agent_commission_in_fbn')->default(false);
            $table->boolean('allow_advance_commission')->default(false);

            // Adding transaction approval control
            $table->boolean('fsn_post_on_approval')->default(false);
            $table->boolean('fbn_post_on_approval')->default(false);
            $table->boolean('cbs_post_on_approval')->default(false);
            $table->boolean('rv_post_on_approval')->default(false);
            $table->boolean('pv_post_on_approval')->default(false);
            $table->boolean('trq_post_on_approval')->default(false);
            $table->boolean('a2a_post_on_approval')->default(false);
            $table->boolean('jv_post_on_approval')->default(false);
            $table->boolean('tsn_tbn_post_on_approval')->default(false);
            $table->boolean('enable_two_step_approval')->default(false);

            // Adding party ledger
            $table->integer('debit_posting_account')->nullable();
            $table->integer('credit_posting_account')->nullable();

            // Adding rounding off
            $table->boolean('rounding_off')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            //
        });
    }
};
