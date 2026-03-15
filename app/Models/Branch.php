<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Branch extends Model
{
    use HasFactory, Filterable;

    protected $table = 'branches';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'city',
        'country_code',
        'phone',
        'manager',
        'supervisor',
        'base_currency',
        'status',

        'account_payable',
        'account_receivable',
        'pdcr_account',
        'pdcp_account',
        'walk_in_customer_account',
        'suspense_account',
        'invalid_payment_order_account',
        'foreign_currency_remittance_account',
        'commission_income_account',
        'commission_expense_account',
        'discount_account',
        'iwt_receivable_account',
        'vat_input_account',
        'vat_output_account',
        'remittance_income_account',
        'counter_income_account',
        'vat_absorb_expense_account',
        'cost_of_sale_account',
        'stock_in_hand_account',
        'depreciation_expense_account',
        'gain_or_loss_on_sale_account',
        'write_off_account',

        'opening_date',
        'closed_upto_date',
        'accept_data_upto_date',

        'startup_alert_period',
        'currency_rate_trend',
        'dashboard_comparison_period',

        'inward_payment_order_limit',
        'outward_remittance_limit',
        'counter_transaction_limit',
        'cash_limit',
        'cash_bank_pay_limit',
        'monthly_transaction_limit',
        'counter_commission_limit',

        'vat_trn',
        'vat_country',
        'default_city',
        'cities',
        'vat_type',
        'vat_percentage',

        'disable_party_id_validation',
        'disable_beneficiary_checking',
        'enable_personalized_marking',
        'show_agent_commission_in_cbs',
        'show_agent_commission_in_fsn',
        'show_agent_commission_in_fbn',
        'allow_advance_commission',
        'fsn_post_on_approval',
        'fbn_post_on_approval',
        'cbs_post_on_approval',
        'rv_post_on_approval',
        'pv_post_on_approval',
        'trq_post_on_approval',
        'a2a_post_on_approval',
        'jv_post_on_approval',
        'tsn_tbn_post_on_approval',
        'enable_two_step_approval',
        'debit_posting_account',
        'credit_posting_account',
        'rounding_off',
    ];

    protected $appends = [
        // 'base_currency',
        'phone_number',
        // 'party_ledgers_account_type',
        // 'complete_profile',
        // 'is_subscribed'
    ];

    protected $casts = [
        'rounding_off' => 'integer',
        'base_currency' => 'integer'
    ];

    public function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->country_code}{$this->phone}"
        );
    }

    public function manager(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'manager');
    }

    public function supervisor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'supervisor');
    }

    public function log(): HasOne
    {
        return $this->hasOne(UserBranch::class, 'branch_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vats(): HasMany
    {
        return $this->hasMany(Vat::class, 'branch_id', 'id');
    }

    public function currency(): HasOne
    {
        return $this->HasOne(Country::class, 'id', 'base_currency');
    }

    public function vat_country(): HasOne
    {
        return $this->HasOne(Country::class, 'id', 'vat_country');
    }

    public function debitAccount(): HasOne
    {
        return $this->HasOne(ChartOfAccount::class, 'id', 'debit_posting_account');
    }

    public function creditAccount(): HasOne
    {
        return $this->HasOne(ChartOfAccount::class, 'id', 'credit_posting_account');
    }
}
