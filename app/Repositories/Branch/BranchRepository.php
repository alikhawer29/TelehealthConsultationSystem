<?php

namespace App\Repositories\Branch;

use Carbon\Carbon;
use App\Models\Vat;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Package;
use App\Models\UserBranch;
use App\Models\Subscription;
use App\Models\ChartOfAccount;
use App\Core\Abstracts\Filters;
use App\Models\CurrencyRegister;
use Database\Seeders\MaturitySeeder;
use Illuminate\Database\Eloquent\Model;
use Database\Seeders\CountryRegisterSeeder;
use Database\Seeders\ClassificationTypeSeeder;
use App\Core\Abstracts\Repository\BaseRepository;
use Database\Seeders\PartyLedgerClassificationSeeder;
use Database\Seeders\TransactionNumberRegisterSeeder;

class BranchRepository extends BaseRepository implements BranchRepositoryContract

{
    protected $model;
    protected $branchId;


    public function setModel(Model $model)
    {
        $this->model = $model;
    }


    public function setBranchId($branchId)
    {
        $this->branchId = $branchId;
    }

    public function getBranchId()
    {
        return $this->branchId;
    }

    public function status($id)
    {
        try {
            $user = request()->user();
            $data = $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 'Blocked' THEN 'Unblocked' ELSE 'Blocked' END")
                ]);


            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function getTotalCount(Filters|null $filter = null)
    {
        try {

            $oneWeekAgo = Carbon::now()->subWeek();
            $now = Carbon::now();
            $totalBranchs = $this->model->filter($filter)->count();
            $lastWeekBranchs = $this->model->filter($filter)->whereBetween('created_at', [$oneWeekAgo, $now])->count();
            if ($totalBranchs > 0) {
                $percentageLastWeek = ($lastWeekBranchs / $totalBranchs) * 100;
            } else {
                // Handle the case where there are no orders to avoid division by zero
                $percentageLastWeek = 0;
            }

            // $currentMonth = Carbon::now()->month;

            // $totalPlayers = $this->model->filter($filter)->count();
            // $currentMonthPlayers = $this->model->whereMonth('created_at', $currentMonth)->count();
            // if ($totalPlayers > 0) {
            //     $percentageCurrentMonth = ($currentMonthPlayers / $totalPlayers) * 100;
            // } else {
            //     // Handle the case where there are no players to avoid division by zero
            //     $percentageCurrentMonth = 0;
            // }

            return  ['total' => $totalBranchs, 'trend' => $percentageLastWeek];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function create($params)
    {
        \DB::beginTransaction();
        try {


            $user = request()->user();
            $businessId = $user->role === 'user' ? $user->id : $user->parent_id;

            // Get the owner's subscription details
            $subscription = Subscription::where(function ($q) use ($businessId, $user) {
                $q->where('user_id', $businessId)
                    ->orWhere('user_id', $user->id);
            })
                ->where('status', 'active')
                ->latest()
                ->first();
            if (!$subscription) {
                throw new \Exception('Subscription not found.');
            }
            // Retrieve the package details based on package_id from the subscription
            $package = Package::find($subscription->package_id);
            if (!$package) {
                throw new \Exception('Package not found.');
            }

            // Retrieve the number of users allowed by the plan
            $maxBranches = $package->branches;

            // Count the number of existing branches under the current owner (parent_id)
            $existingUsers = Branch::where(function ($q) use ($businessId, $user) {
                $q->where('user_id', $businessId)
                    ->orWhere('user_id', $user->id);
            })
                ->where('status', 'Unblocked')->count();

            // Check if the number of existing users exceeds the maximum allowed
            if ($existingUsers >= $maxBranches) {
                throw new \Exception('You have reached the maximum number of branches. To create a new branch, you will need to upgrade your package.');
            }

            $branch = $this->model->create(
                [
                    'user_id' => $user->id,
                    'name' => $params['name'],
                    'address' => $params['address'],
                    'city' => $params['city'],
                    'country_code' => $params['country_code'],
                    'phone' => $params['phone'],
                    'manager' => $params['manager'],
                    'supervisor' => $params['supervisor'],
                    'base_currency' => $params['base_currency'],
                    'status' => 'Unblocked',
                    'opening_date' => $params['opening_date'],
                    'closed_upto_date' => $params['closed_upto_date'],
                    'accept_data_upto_date' => $params['accept_data_upto_date'],
                    'startup_alert_period' => $params['startup_alert_period'],
                    'currency_rate_trend' => $params['currency_rate_trend'],
                    'dashboard_comparison_period' => $params['dashboard_comparison_period'],
                    'inward_payment_order_limit' => $params['inward_payment_order_limit'],
                    'outward_remittance_limit' => $params['outward_remittance_limit'],
                    'counter_transaction_limit' => $params['counter_transaction_limit'],
                    'cash_limit' => $params['cash_limit'],
                    'cash_bank_pay_limit' => $params['cash_bank_pay_limit'],
                    'monthly_transaction_limit' => $params['monthly_transaction_limit'],
                    'counter_commission_limit' => $params['counter_commission_limit'],
                    'vat_trn' => $params['vat_trn'],
                    'vat_country' => $params['vat_country'],
                    'default_city' => $params['default_city'],
                    'cities' => $params['cities'],
                    'vat_type' => $params['vat_type'],
                    'vat_percentage' => $params['vat_percentage'],
                    'disable_party_id_validation' => $params['disable_party_id_validation'],
                    'disable_beneficiary_checking' => $params['disable_beneficiary_checking'],
                    'enable_personalized_marking' => $params['enable_personalized_marking'],
                    'show_agent_commission_in_cbs' => $params['show_agent_commission_in_cbs'],
                    'show_agent_commission_in_fsn' => $params['show_agent_commission_in_fsn'],
                    'show_agent_commission_in_fbn' => $params['show_agent_commission_in_fbn'],
                    'allow_advance_commission' => $params['allow_advance_commission'],
                    'fsn_post_on_approval' => $params['fsn_post_on_approval'],
                    'fbn_post_on_approval' => $params['fbn_post_on_approval'],
                    'cbs_post_on_approval' => $params['cbs_post_on_approval'],
                    'rv_post_on_approval' => $params['rv_post_on_approval'],
                    'pv_post_on_approval' => $params['pv_post_on_approval'],
                    'trq_post_on_approval' => $params['trq_post_on_approval'],
                    'a2a_post_on_approval' => $params['a2a_post_on_approval'],
                    'jv_post_on_approval' => $params['jv_post_on_approval'],
                    'tsn_tbn_post_on_approval' => $params['tsn_tbn_post_on_approval'],
                    'enable_two_step_approval' => $params['enable_two_step_approval'],
                    'debit_posting_account' => $params['debit_posting_account'],
                    'credit_posting_account' => $params['credit_posting_account'],
                    'rounding_off' => $params['rounding_off'],
                ]
            );

            // Fetch the template accounts
            $templateAccounts = ChartOfAccount::where('is_template', true)->get();

            // Create a mapping for template account IDs to their new IDs after cloning
            $accountMappings = [];

            // Clone the accounts while handling parent_account_id relationships
            $clonedAccounts = $templateAccounts->map(function ($templateAccount) use (&$accountMappings, $branch, $businessId, $user) {
                // If the account has a parent, get the new parent ID from the account mappings
                $parentAccountId = null;
                if ($templateAccount->parent_account_id) {
                    $parentAccountId = $accountMappings[$templateAccount->parent_account_id] ?? null;
                }

                // Prepare the cloned account data
                $clonedAccountData = [
                    'branch_id' => $branch->id, // Set this dynamically based on the branch context
                    'parent_id' => $businessId, // Adjust this as needed for business or branch
                    'account_type' => $templateAccount->account_type,
                    'account_name' => $templateAccount->account_name,
                    'account_code' => $templateAccount->account_code,
                    'parent_account_id' => $parentAccountId, // Map the parent_account_id to the new ID
                    'level' => $templateAccount->level,
                    'status' => $templateAccount->status,
                    'description' => $templateAccount->description,
                    'created_by' => $user->id, // Set this dynamically (based on user context)
                    'is_template' => false, // Mark as non-template
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Insert the cloned account and capture the new ID
                $newAccount = ChartOfAccount::create($clonedAccountData);

                // Store the mapping from the original template account ID to the new account ID
                $accountMappings[$templateAccount->id] = $newAccount->id;

                // Return the cloned account data (or modify as per requirements)
                return $newAccount;
            });

            // Step 3:Fetch specific accounts for default mappings

            $accountsReceivable = $this->fetchAccount($branch->id, 'Account Receivable');
            $accountsPayable = $this->fetchAccount($branch->id, 'Accounts Payable');
            $pdcr_account = $this->fetchAccount($branch->id, 'Post Dated Cheques Receivable');
            $pdcp_account = $this->fetchAccount($branch->id, 'Post Dated Cheques Payable');
            $walk_in_customer_account = $this->fetchAccount($branch->id, 'Cash Customer');
            $suspense_account = $this->fetchAccount($branch->id, 'Bank Suspense Account');
            $invalid_payment_order_account = $this->fetchAccount($branch->id, 'Inward Remittance Payment');
            $foreign_currency_remittance_account = $this->fetchAccount($branch->id, 'FC Remittance Account');
            $commission_income_account = $this->fetchAccount($branch->id, 'Commission Received Account');
            $commission_expense_account = $this->fetchAccount($branch->id, 'Commission Expense');
            $discount_account = $this->fetchAccount($branch->id, 'Discount EXP Account');
            $iwt_receivable_account = $this->fetchAccount($branch->id, 'IWT Recievable Account');
            $remittance_income_account = $this->fetchAccount($branch->id, 'Remittance Revenue Account');
            $counter_income_account = $this->fetchAccount($branch->id, 'Counter Income');
            $cost_of_sale_account = $this->fetchAccount($branch->id, 'Cost Of Sale Account');
            $stock_in_hand_account = $this->fetchAccount($branch->id, 'Inventory');
            $depreciation_expense_account = $this->fetchAccount($branch->id, 'Depreciation Expense');
            $gain_or_loss_on_sale_account = $this->fetchAccount($branch->id, 'MISC Expense');
            $write_off_account = $this->fetchAccount($branch->id, 'MISC Expense');
            $depreciation_expense_account = $this->fetchAccount($branch->id, 'Depreciation Expense');

            $defaultDebitPostingAccount = ChartOfAccount::where('id', $branch->debit_posting_account)
                ->first();

            $defaultCreditPostingAccount = ChartOfAccount::where('id', $branch->credit_posting_account)
                ->first();

            $debitPostingAccount = $this->fetchAccount($branch->id, $defaultDebitPostingAccount->account_name);
            $creditPostingAccount = $this->fetchAccount($branch->id, $defaultCreditPostingAccount->account_name);

            $branch->update([
                'account_payable' => $accountsReceivable->id,
                'account_receivable' => $accountsPayable->id,
                'pdcr_account' => $pdcr_account->id,
                'pdcp_account' => $pdcp_account->id,
                'walk_in_customer_account' => $walk_in_customer_account->id,
                'suspense_account' => $suspense_account->id,
                'invalid_payment_order_account' => $invalid_payment_order_account->id,
                'foreign_currency_remittance_account' => $foreign_currency_remittance_account->id,
                'commission_income_account' => $commission_income_account->id,
                'commission_expense_account' => $commission_expense_account->id,
                'discount_account' => $discount_account->id,
                'iwt_receivable_account' => $iwt_receivable_account->id,
                'vat_input_account' => $accountsPayable->id,
                'vat_output_account' => $accountsPayable->id,
                'remittance_income_account' => $remittance_income_account->id,
                'counter_income_account' => $counter_income_account->id,
                'vat_absorb_expense_account' => $accountsPayable->id,
                'cost_of_sale_account' => $cost_of_sale_account->id,
                'stock_in_hand_account' => $stock_in_hand_account->id,
                'depreciation_expense_account' => $depreciation_expense_account->id,
                'gain_or_loss_on_sale_account' => $gain_or_loss_on_sale_account->id,
                'write_off_account' => $write_off_account->id,
                'debit_posting_account' => $debitPostingAccount->id,
                'credit_posting_account' => $creditPostingAccount->id,
            ]);

            // default active branch
            $user->update(['selected_branch' => $branch->id]);

            // User branch logs with that user
            UserBranch::create([
                'business_id' => $businessId,
                'employee_id' => $user->id,
                'status' => 'active',
                'branch_id' => $branch->id,
            ]);

            if ($params['vat_type'] === 'variable' && !empty($params['vats']) && is_array($params['vats'])) {
                // User VATs with that branch
                foreach ($params['vats'] as $vat) {
                    Vat::create([
                        'business_id' => $businessId,
                        'branch_id' => $branch->id,
                        'title' => $vat['title'],
                        'percentage' => $vat['percentage'],
                    ]);
                }
            }



            // Chart of account - 64 entries

            // Use the user_id when running the seeder for country register - 240 entries
            $this->call(CountryRegisterSeeder::class, false, ['user_id' => $user->id, 'branch_id' => $branch->id]);

            // Use the user_id when running the seeder for classification types - 10 entries
            $this->call(ClassificationTypeSeeder::class, false, ['user_id' => $user->id, 'branch_id' => $branch->id]);

            // Use the user_id when running the seeder for classification - party ledger - 2 entries
            $this->call(PartyLedgerClassificationSeeder::class, false, ['user_id' => $user->id, 'branch_id' => $branch->id]);

            // Use the user_id when running the seeder for maturity alert types - 4 entries
            $this->call(MaturitySeeder::class, false, ['user_id' => $user->id, 'branch_id' => $branch->id]);

            // Use the user_id when running the transaction number register - 19 entries
            $this->call(TransactionNumberRegisterSeeder::class, false, ['user_id' => $user->id, 'branch_id' => $branch->id]);


            // register base currency
            $currencyData = Country::where('id', $params['base_currency'])->first();
            CurrencyRegister::create([
                'currency_code' => $currencyData->currency,
                'currency_name' => $currencyData->currency_name,
                'rate_type' => 'Multiply',
                'currency_type' => 'Regular Currency',
                'rate_variation' => 1,
                'allow_online_rate' => 0,
                'allow_auto_pairing' => 0,
                'allow_second_preference' => 0,
                'restrict_pair' => 0,
                'special_rate_currency' => 0,
                'created_by' => $user->id,
                'parent_id' => $user->id,
                'is_custom' => 0,
                'branch_id' => $branch->id
            ]);
            \DB::commit();
            return $branch;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }



    public function updateBranch($id, array $params)
    {
        \DB::beginTransaction();
        try {

            $businessId = request()->user()->id;

            // Update branch details
            $branch = $this->model->where('id', $id)
                ->update(
                    [
                        'user_id' => request()->user()->id,
                        'name' => $params['name'],
                        'address' => $params['address'],
                        'city' => $params['city'],
                        'country_code' => $params['country_code'],
                        'phone' => $params['phone'],
                        'manager' => $params['manager'],
                        'supervisor' => $params['supervisor'],
                        'base_currency' => $params['base_currency'],

                        'opening_date' => $params['opening_date'],
                        'closed_upto_date' => $params['closed_upto_date'],
                        'accept_data_upto_date' => $params['accept_data_upto_date'],

                        'startup_alert_period' => $params['startup_alert_period'],
                        'currency_rate_trend' => $params['currency_rate_trend'],
                        'dashboard_comparison_period' => $params['dashboard_comparison_period'],

                        'inward_payment_order_limit' => $params['inward_payment_order_limit'],
                        'outward_remittance_limit' => $params['outward_remittance_limit'],
                        'counter_transaction_limit' => $params['counter_transaction_limit'],
                        'cash_limit' => $params['cash_limit'],
                        'cash_bank_pay_limit' => $params['cash_bank_pay_limit'],
                        'monthly_transaction_limit' => $params['monthly_transaction_limit'],
                        'counter_commission_limit' => $params['counter_commission_limit'],

                        'vat_trn' => $params['vat_trn'],
                        'vat_country' => $params['vat_country'],
                        'default_city' => $params['default_city'],
                        'cities' => $params['cities'],
                        'vat_type' => $params['vat_type'],
                        'vat_percentage' => $params['vat_percentage'],

                        'disable_party_id_validation' => $params['disable_party_id_validation'],
                        'disable_beneficiary_checking' => $params['disable_beneficiary_checking'],
                        'enable_personalized_marking' => $params['enable_personalized_marking'],
                        'show_agent_commission_in_cbs' => $params['show_agent_commission_in_cbs'],
                        'show_agent_commission_in_fsn' => $params['show_agent_commission_in_fsn'],
                        'show_agent_commission_in_fbn' => $params['show_agent_commission_in_fbn'],
                        'allow_advance_commission' => $params['allow_advance_commission'],

                        'fsn_post_on_approval' => $params['fsn_post_on_approval'],
                        'fbn_post_on_approval' => $params['fbn_post_on_approval'],
                        'cbs_post_on_approval' => $params['cbs_post_on_approval'],
                        'rv_post_on_approval' => $params['rv_post_on_approval'],
                        'pv_post_on_approval' => $params['pv_post_on_approval'],

                        'trq_post_on_approval' => $params['trq_post_on_approval'],
                        'a2a_post_on_approval' => $params['a2a_post_on_approval'],
                        'jv_post_on_approval' => $params['jv_post_on_approval'],
                        'tsn_tbn_post_on_approval' => $params['tsn_tbn_post_on_approval'],
                        'enable_two_step_approval' => $params['enable_two_step_approval'],

                        'debit_posting_account' => $params['debit_posting_account'],
                        'credit_posting_account' => $params['credit_posting_account'],

                        'rounding_off' => $params['rounding_off'],
                    ]
                );

            if ($params['vat_type'] === 'variable' && !empty($params['vats']) && is_array($params['vats'])) {

                Vat::where('branch_id', $id)
                    ->delete();
                // User VATs with that branch
                foreach ($params['vats'] as $vat) {
                    Vat::create([
                        'business_id' => $businessId,
                        'branch_id' => $id,
                        'title' => $vat['title'],
                        'percentage' => $vat['percentage'],
                    ]);
                }
            }

            $currencyData = Country::find($params['base_currency']);

            if ($currencyData) {
                $currencyDetails = [
                    'currency_code' => $currencyData->currency,
                    'currency_name' => $currencyData->currency_name,
                ];

                CurrencyRegister::updateOrCreate(
                    [
                        'branch_id' => $id,
                        'parent_id' => $businessId,
                    ],
                    $currencyDetails + [
                        'rate_type' => 'Multiply',
                        'currency_type' => 'Regular Currency',
                        'rate_variation' => 1,
                        'allow_online_rate' => 0,
                        'allow_auto_pairing' => 0,
                        'allow_second_preference' => 0,
                        'restrict_pair' => 0,
                        'special_rate_currency' => 0,
                        'created_by' => $businessId,
                        'is_custom' => 0,
                    ]
                );
            }

            \DB::commit();
            return true;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    //over ride call method
    protected function call($class, $silent = false, $parameters = [])
    {
        $seeder = app($class);

        if (!empty($parameters)) {
            $seeder->run($parameters['user_id'], $parameters['branch_id']);
        } else {
            $seeder->run();
        }
    }

    private function fetchAccount($branchId, $accountName)
    {
        return ChartOfAccount::where('branch_id', $branchId)
            ->where('account_name', $accountName)
            ->first();
    }

    private function convertToUTCTime($localTime)
    {
        $timezone = 'Asia/Karachi';
        // Create a Carbon instance for the local time and timezone
        $localDateTime = Carbon::createFromFormat('H:i', $localTime, $timezone);
        // Convert the local time to UTC
        $utcDateTime = $localDateTime->setTimezone('UTC');
        // Return the UTC time in 'H:i' format
        return $utcDateTime->format('H:i');
    }
}
