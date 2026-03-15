<?php

namespace App\Http\Controllers\User\User;

use App\Models\User;
use App\Models\Branch;
use App\Models\Package;
use App\Models\UserBranch;
use App\Models\Subscription;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChartOfAccount;
use App\Models\AccessManagement;
use Illuminate\Http\JsonResponse;
use App\Filters\User\UsersFilters;
use App\Models\AccountsPermission;
use App\Filters\Admin\BranchFilters;
use App\Http\Controllers\Controller;
use App\Repositories\User\UserRepository;
use App\Http\Requests\Auth\UserCreateRequest;
use App\Http\Requests\Auth\UserUpdateRequest;
use App\Repositories\Branch\BranchRepository;

class UserController extends Controller
{

    private UserRepository $user;
    private BranchRepository $branch;


    public function __construct(UserRepository $userRepo, User $user, BranchRepository $branchRepo, Branch $branch)
    {
        $this->user = $userRepo;
        $this->user->setModel($user);

        $this->branch = $branchRepo;
        $this->branch->setModel($branch);
    }


    public function index(Request $request, UsersFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
                'role' => 'employee',
                'parent_id' => 1,
            ]);

            $users = $this->user->paginate(
                request('per_page', 10),
                filter: $filter,
            );

            $data = api_successWithData('user listing', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function settings(): JsonResponse
    {
        try {

            $user = request()->user();
            $canCreate = true;
            $isSubscribed = true;
            $message = '';

            $business_id = $user->role === 'employee' ? $user->parent_id : $user->id;

            // Fetch the active subscription
            $subscription = Subscription::where('status', 'active')
                ->where('business_id', $business_id)
                ->latest()
                ->first();

            // If there's no active subscription or subscription expired, disallow user creation
            if (!$subscription || $subscription->expire_date < now()) {
                $canCreate = false;
                $isSubscribed = false;
                $message = 'Your subscription has expired or is not active. Please renew your subscription to create new users.';
            }

            // Check subscription and package constraints only if subscription exists
            if ($subscription && $subscription->package_id) {
                $package = Package::find($subscription->package_id);

                if ($package && isset($package->no_of_users)) {
                    $maxUsers = (int) $package->no_of_users;

                    // Count active users linked to the parent ID
                    $existingUsers = User::where('parent_id', $user->parent_id)
                        ->where('status', 1)
                        ->count();

                    // If the number of users exceeds the max limit, prevent creation
                    if ($existingUsers >= $maxUsers) {
                        $canCreate = false;
                        $message = 'You have reached the maximum number of users. To create a new user, you will need to upgrade your package.';
                    }
                }
            }

            $data = api_successWithData('user settings', compact('canCreate', 'isSubscribed', 'message'));

            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $user = $this->user
                ->findById(
                    $id,
                    relations: [
                        'timeSlots',
                        'accessRights',
                        'accountsPermission'
                    ]
                );
            // Fetch default access rights structure
            $defaultPermissions = $this->getDefaultPermissionStructure();
            // Transform access rights to match default structure
            $accessRights = $this->mergeDefaultAndUserPermissions($defaultPermissions, $user->accessRights);
            // Add transformed access rights to user data
            $user->access_rights = $accessRights;
            unset($user->accessRights);

            // Fetch default permission structure (default chart of accounts)
            $defaultAccountPermissions = $this->getAccountPermissionsData();
            // Transform chart of accounts with granted status
            $accountPermissions = $user->accountsPermission->pluck('granted', 'chart_of_account_code');
            $defaultAccounts = $this->formatAccountsWithPermissions($defaultAccountPermissions, $accountPermissions);
            $user->accounts_permission = $defaultAccounts;
            unset($user->accountsPermission);

            $data = api_successWithData('user details', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(UserCreateRequest $request)
    {
        try {

            $this->user->create($request->validated(), true);
            $data = api_success('User created successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function update($id, UserUpdateRequest $request)
    {
        try {
            $this->user->updateUser($id, $request->validated());
            $data = $this->user->findById($id);
            $data = api_successWithData('updated successfully', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function status($id)
    {
        try {
            $this->user->status($id);
            $user = $this->user->findById($id);
            $data = api_successWithData('status has been updated', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->user->findById($id); // Find the user or fail if not found
            $user->email = $user->email . '_' . now()->timestamp;
            $user->save();
            $user->delete();
            $data = api_success('Deleted successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function branches($id, BranchFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'owner' => $id,
            ]);
            $data = $this->branch
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                );

            $data = api_successWithData('branch listing', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    //for access permissions of modules
    public function accessRights(): JsonResponse
    {
        try {
            $ownerId = request()->user()->id;

            // Retrieve the branches for the owner
            $branches = Branch::where('user_id', $ownerId)->get(['id', 'name']);
            // Prepare the branch selection permissions dynamically
            $branchSelection = [];
            foreach ($branches as $key => $branch) {
                $branchSelection[$branch->name] = ($key === 0);
            }

            $permissions = [
                'master' => [
                    'chart_of_account' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'party_ledger' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'walk_in_customer' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'teller_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'classification_master' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'warehouse_master' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'cb_classification_master' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'beneficiary_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'document_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'salesman_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'commission_master' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'currency_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'country_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'office_location_master' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'cost_center_register' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                    'group_master' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                ],
                'transactions' => [
                    'journal_voucher' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'receipt_voucher' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'payment_voucher' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'internal_payment_voucher' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'bank_transactions' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'pdcr_payment_voucher' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'suspense_voucher' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'suspense_posting' => [
                        'post' => false,
                        'cancel_posting' => false,
                    ],
                    'account_to_account' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'foreign_currency_deal' => [
                        'view' => false,
                        'create_single_deal' => false,
                        'create_multiple_deals' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'tmn_currency_deal' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'currency_transfer' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'deal_register' => [
                        'print' => false,
                    ],
                    'inward_payment_order' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'inward_payment' => [
                        'pay' => false,
                        'print' => false,
                    ],
                    'inward_payment_cancellation' => [
                        'cancel_payment' => false,
                    ],
                    'outward_remittance' => [
                        'view' => false,
                        'create' => false,
                        'back_to_back_entry' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'outward_remittance_register' => [
                        'post' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'application_printing' => [
                        'print' => false,
                    ],
                    'ttr_register' => [
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'print' => false,
                    ],
                    'rate_of_exchange' => [
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                    ],
                ],
                'process' => [
                    'pdc_processing' => [
                        'process' => false,
                    ],
                    'pdc_payment_processing' => [
                        'settle' => false,
                        'return_unpaid' => false,
                        'revert' => false,
                    ],
                    'profit_loss_posting' => [
                        're_calculate_closing_rate' => false,
                        'rate_revaluation' => false,
                        'profit_loss_balance_conversion' => false,
                        'profit_loss_posting' => false,
                        'print' => false,
                    ],
                    'periodic_account_locking' => [
                        'process' => false,
                    ],
                    'balance_write_off' => [
                        'post' => false,
                    ],
                    'transaction_approval' => [
                        // Add permissions as required
                    ],
                    'transaction_lock_register' => [
                        'rate_release' => false,
                        'delete' => false,
                    ],
                ],
                'reports' => [
                    'journal_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'walk_in_customer_statement' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'walk_in_customer_outstanding_balance' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'statement_of_account' => [
                        'view' => false,
                        'email_as_pdf' => false,
                        'email_as_excel' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'outstanding_balance' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'expense_journal' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'cash_bank_balance' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'post_dated_cheque_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'vat_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'budgeting_forecasting_report' => [
                        'view' => false,
                        'create_projection' => false,
                        'edit_projection' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'currency_transfer_register_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'outward_remittance_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'outward_remittance_enquiry' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'inward_remittance_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'deal_register_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'account_turnover_report' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'trial_balance' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'profit_loss_statement' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'balance_sheet' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'exchange_profit_loss' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                    'account_enquiry' => [
                        'view' => false,
                        'export_to_excel' => false,
                        'export_to_pdf' => false,
                    ],
                ],
                'administration' => [
                    'user_maintenance' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'block_unblock' => false,
                    ],
                    'password_reset' => [
                        'reset' => true,
                    ],

                    'branch_management' => [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'block_unblock' => false,
                    ],
                    'cheque_register' => [
                        'create_cheque_book' => false,
                        'delete_cheque_book' => false,
                        'delete_single_cheque' => false,
                    ],
                    'transaction_log' => [
                        'print' => false,
                    ],
                    'user_logs' => [
                        'view' => false,
                    ],
                    "branch_selection" => $branchSelection, // Dynamic branch permissions

                    'maturity_alert' => [
                        'print' => false,
                    ],
                    'system_integrity_check' => [
                        'view' => false,
                    ],
                    'deal_register_updation' => [
                        'update_deal_register' => false,
                    ],
                    'subscription_logs' => [
                        'view_subscription_logs' => false,
                        'renew_subscription' => false,
                        'change_subscription' => false,
                        'request_custom_subscription' => false,
                        'buy_custom_subscription' => false,
                    ],
                    'transaction_number_register' => [
                        'edit' => false,
                    ],
                ],

            ];
            $data = api_successWithData('permissions', $permissions);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    //chart of accounts permission
    public function accountPermissions(): JsonResponse
    {
        try {
            $accounts = ChartOfAccount::whereNull('parent_account_id')
                ->where('is_template', true)
                ->orderBy('account_code') // Order by account_code
                ->get();

            // Format the accounts recursively
            $data = $this->formatAccounts($accounts);
            $response = api_successWithData('Accounts Permissions', $data);
            return response()->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    //chart of accounts formation
    private function formatAccounts($accounts)
    {
        $formattedAccounts = [];

        foreach ($accounts as $account) {

            $children = $account->children->sortBy('account_code');

            $formattedAccounts[] = [
                'id' => $account->id,
                'level' => $account->level,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'status' => $account->status,
                'children' => $this->formatAccounts($children)
            ];
        }

        return $formattedAccounts;
    }

    //access permissions modules of other users
    public function accessRightsOtherUser($id): JsonResponse
    {
        try {
            // Fetch all permissions for the given employee
            $permissions = AccessManagement::where('employee_id', $id)->get();

            // Fetch default permission structures dynamically
            $defaultPermissions = $this->getDefaultPermissionStructure();

            // Initialize structured permissions with the default structure
            $structuredPermissions = $defaultPermissions;

            foreach ($permissions as $permission) {
                $parent = $permission->parent; // e.g., 'master'
                $module = $permission->module; // e.g., 'chart_of_account'
                $permissionType = $permission->permission; // e.g., 'view'
                $granted = (bool)$permission->granted; // Convert to boolean

                // Ensure the parent exists
                if (!isset($structuredPermissions[$parent])) {
                    $structuredPermissions[$parent] = [];
                }

                // Ensure the module exists under the parent
                if (!isset($structuredPermissions[$parent][$module])) {
                    // Initialize the module permissions with defaults
                    $structuredPermissions[$parent][$module] = [];
                }

                // Assign the specific permission, overriding the default if it exists
                $structuredPermissions[$parent][$module][$permissionType] = $granted;
            }

            $data = api_successWithData('Other user permissions', $structuredPermissions);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    /**
     * Simulate fetching default permission structures from a database or config
     */
    private function getDefaultPermissionStructure(): array
    {
        $ownerId = request()->user()->id;
        // Retrieve the branches for the owner
        $branches = Branch::where('user_id', $ownerId)->get(['id', 'name']);
        // Prepare the branch selection permissions dynamically
        $branchSelection = [];
        foreach ($branches as $key => $branch) {
            $branchSelection[$branch->name] = ($key === 0);
        }
        return [
            'master' => [
                'chart_of_account' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'party_ledger' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'walk_in_customer' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'teller_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'classification_master' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'warehouse_master' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'cb_classification_master' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'beneficiary_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'document_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'salesman_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'commission_master' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'currency_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'country_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'office_location_master' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'cost_center_register' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'group_master' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            'transactions' => [
                'journal_voucher' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'receipt_voucher' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'payment_voucher' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'internal_payment_voucher' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'bank_transactions' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'pdcr_payment_voucher' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'suspense_voucher' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'suspense_posting' => [
                    'post' => false,
                    'cancel_posting' => false,
                ],
                'account_to_account' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'foreign_currency_deal' => [
                    'view' => false,
                    'create_single_deal' => false,
                    'create_multiple_deals' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'tmn_currency_deal' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'currency_transfer' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'deal_register' => [
                    'print' => false,
                ],
                'inward_payment_order' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'inward_payment' => [
                    'pay' => false,
                    'print' => false,
                ],
                'inward_payment_cancellation' => [
                    'cancel_payment' => false,
                ],
                'outward_remittance' => [
                    'view' => false,
                    'create' => false,
                    'back_to_back_entry' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'outward_remittance_register' => [
                    'post' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'application_printing' => [
                    'print' => false,
                ],
                'ttr_register' => [
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'print' => false,
                ],
                'rate_of_exchange' => [
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            'process' => [
                'pdc_processing' => [
                    'process' => false,
                ],
                'pdc_payment_processing' => [
                    'settle' => false,
                    'return_unpaid' => false,
                    'revert' => false,
                ],
                'profit_loss_posting' => [
                    're_calculate_closing_rate' => false,
                    'rate_revaluation' => false,
                    'profit_loss_balance_conversion' => false,
                    'profit_loss_posting' => false,
                    'print' => false,
                ],
                'periodic_account_locking' => [
                    'process' => false,
                ],
                'balance_write_off' => [
                    'post' => false,
                ],
                'transaction_approval' => [
                    // Add permissions as required
                ],
                'transaction_lock_register' => [
                    'rate_release' => false,
                    'delete' => false,
                ],
            ],
            'reports' => [
                'journal_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'walk_in_customer_statement' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'walk_in_customer_outstanding_balance' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'statement_of_account' => [
                    'view' => false,
                    'email_as_pdf' => false,
                    'email_as_excel' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'outstanding_balance' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'expense_journal' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'cash_bank_balance' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'post_dated_cheque_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'vat_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'budgeting_forecasting_report' => [
                    'view' => false,
                    'create_projection' => false,
                    'edit_projection' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'currency_transfer_register_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'outward_remittance_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'outward_remittance_enquiry' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'inward_remittance_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'deal_register_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'account_turnover_report' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'trial_balance' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'profit_loss_statement' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'balance_sheet' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'exchange_profit_loss' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
                'account_enquiry' => [
                    'view' => false,
                    'export_to_excel' => false,
                    'export_to_pdf' => false,
                ],
            ],
            'administration' => [
                'user_maintenance' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'block_unblock' => false,
                ],
                'password_reset' => [
                    'reset' => true,
                ],

                'branch_management' => [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                    'block_unblock' => false,
                ],
                'cheque_register' => [
                    'create_cheque_book' => false,
                    'delete_cheque_book' => false,
                    'delete_single_cheque' => false,
                ],
                'transaction_log' => [
                    'print' => false,
                ],
                'user_logs' => [
                    'view' => false,
                ],
                "branch_selection" => $branchSelection, // Dynamic branch permissions

                'maturity_alert' => [
                    'print' => false,
                ],
                'system_integrity_check' => [
                    'view' => false,
                ],
                'deal_register_updation' => [
                    'update_deal_register' => false,
                ],
                'subscription_logs' => [
                    'view_subscription_logs' => false,
                    'renew_subscription' => false,
                    'change_subscription' => false,
                    'request_custom_subscription' => false,
                    'buy_custom_subscription' => false,
                ],
                'transaction_number_register' => [
                    'edit' => false,
                ],
            ],

        ];
    }

    private function mergeDefaultAndUserPermissions(array $defaultPermissions, $userAccessRights)
    {
        // Flatten user access rights into an associative array
        $userPermissions = [];
        foreach ($userAccessRights as $accessRight) {
            $userPermissions[$accessRight->parent][$accessRight->module][$accessRight->permission] = (bool) $accessRight->granted;
        }

        // Merge user permissions into default structure
        foreach ($defaultPermissions as $parent => $modules) {
            foreach ($modules as $module => $permissions) {
                foreach ($permissions as $permission => $defaultValue) {
                    // Set true/false: Override default if user has a specific permission
                    $defaultPermissions[$parent][$module][$permission] =
                        isset($userPermissions[$parent][$module][$permission])
                        ? $userPermissions[$parent][$module][$permission]
                        : $defaultValue;
                }
            }
        }

        return $defaultPermissions;
    }

    //default chart of account permissions
    private function getAccountPermissionsData(): array
    {
        $accounts = ChartOfAccount::whereNull('parent_account_id')
            ->where('is_template', 1)
            ->orderBy('account_code') // Order by account_code
            ->get();

        // Format the accounts recursively
        return $this->formatAccounts($accounts);
    }

    //default chart of account permissions formation
    private function formatAccountsWithPermissions($defaultAccounts, $accountPermissions)
    {
        return collect($defaultAccounts)->map(function ($account) use ($accountPermissions) {
            return [
                'id' => $account['id'],
                'level' => $account['level'],
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'granted' => (bool) $accountPermissions->get($account['account_code'], false), // Default to false
                'children' => $this->formatAccountsWithPermissions($account['children'] ?? [], $accountPermissions),
            ];
        })->toArray();
    }

    private function getAccountPermissionsDataOther(): array
    {
        $accounts = ChartOfAccount::whereNull('parent_account_id')
            ->where('is_template', 1)
            ->orderBy('account_code') // Order by account_code
            ->get();

        // Format the accounts recursively
        return $this->formatAccounts($accounts);
    }

    //account permissions of other users
    public function accountPermissionsOther($id): JsonResponse
    {
        try {
            // Fetch all permissions for the given employee (return an empty collection if no permissions found)
            $accountPermissions = AccountsPermission::where('employee_id', $id)->pluck('granted', 'chart_of_account_code');

            // Check if the employee has permissions
            if ($accountPermissions->isEmpty()) {
                // Handle case when no permissions are found
                $data = api_error('No permissions found for this employee.');
                return response()->json($data, Response::HTTP_NOT_FOUND);
            }

            // Fetch default permission structures dynamically
            $defaultAccountPermissions = $this->getAccountPermissionsDataOther();

            // Check if default permissions exist
            if (!$defaultAccountPermissions) {
                $data = api_error('No default account permissions available.');
                return response()->json($data, Response::HTTP_NOT_FOUND);
            }

            // Format accounts with permissions
            $defaultAccounts = $this->formatAccountsWithPermissions($defaultAccountPermissions, $accountPermissions);

            // Return a successful response with the formatted data
            $data = api_successWithData('Other user permissions', $defaultAccounts);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Return error response if an exception occurs
            $data = api_error('Error processing account permissions: ' . $e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function userLogs(Request $request): JsonResponse
    {
        try {
            $user = request()->user();
            $parentId = $user->role === 'user' ? $user->id : $user->parent_id;

            // Get search, page, and per_page parameters
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10); // Default to 10 items per page
            $page = $request->input('page', 1); // Default to page 1

            // Query with search filter and pagination
            $logs = UserLoginLog::with('user:id,user_name,role') // Include role in the user data
                ->whereIn('user_id', function ($query) use ($user, $parentId) {
                    // Always include the owner (user) and employees under the same parent_id
                    $query->select('id')
                        ->from('users')
                        ->where('parent_id', $parentId)
                        ->orwhere('id', $parentId); // Include both owner and employee
                })
                ->when($search, function ($query, $search) {
                    $query->whereHas('user', function ($query) use ($search) {
                        $query->where('user_name', 'like', "%$search%");
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json(api_successWithData('user logs', $logs), Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }


    public function branchSelection(): JsonResponse
    {
        try {
            $user = request()->user();
            $userSelectedBranch = $user->selected_branch;

            // Determine business ID (owner ID)
            $business_id = $user->role == 'user' ? $user->id : $user->parent_id;

            // Fetch all employees under the business
            $getAllCompanyUsers = User::where('parent_id', $business_id)
                ->where('role', 'employee')
                ->pluck('id');

            // Ensure the business owner is included
            $allUserIds = $getAllCompanyUsers->push($business_id);

            // Get the branches and mark the selected one
            $branches = Branch::whereIn('user_id', $allUserIds)
                ->where('status', 'Unblocked')
                ->select('id', 'name', 'status')
                ->get()
                ->map(function ($branch) use ($userSelectedBranch) {
                    $branch->is_selected = $branch->id == $userSelectedBranch ? 'active' : 'inactive';
                    return $branch;
                });

            return response()->json(api_successWithData('User branches list', $branches), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }


    public function branchSelectionDetails($id): JsonResponse
    {
        try {

            // Fetch branch details along with manager, supervisor, and total number of users
            $branch = Branch::with('manager:id,user_name', 'supervisor:id,user_name')
                ->where('id', $id)
                ->select('id', 'name', 'manager', 'supervisor', 'status')
                ->first();

            // Check if branch exists
            if (!$branch) {
                return response()->json(api_error('Branch not found'), Response::HTTP_NOT_FOUND);
            }

            // Get the total number of users registered by this branch
            $totalUsers = UserBranch::where('branch_id', $id)->count();

            $branch['total_users'] = $totalUsers;

            return response()->json(api_successWithData('Branch details and total users', $branch), Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle any errors
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function branchSelectionUpdate($id, Request $request): JsonResponse
    {
        try {
            $user = request()->user();
            $user->update(['selected_branch' => $id]);
            return response()->json(api_success('branch updated'), Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function users(): JsonResponse
    {
        try {
            $user = request()->user();
            $businessId = $user->role === 'user' ? $user->id : $user->parent_id;

            // Get users or employees related to the business
            $users = User::where('status', 1)
                ->where('parent_id', $businessId)
                ->get(['id', 'user_name']);

            // // If no employees found, include the logged-in user's own record
            // if ($users->isEmpty()) {
            //     $users = collect([['id' => $user->id, 'user_name' => $user->user_name]]);
            // }

            $data = api_successWithData('users', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    //for login user - access rights
    public function loginUserAccessRights(): JsonResponse
    {
        try {
            $user = request()->user();
            $id = $user->id;
            $role = $user->role;

            $user = $this->user
                ->findById(
                    $id,
                    relations: [
                        'accessRights',
                    ]
                );

            // Fetch default access rights structure
            $defaultPermissions = $this->getDefaultPermissionStructure();

            // Transform access rights to match default structure
            $accessRights = $this->mergeDefaultAndUserPermissions($defaultPermissions, $user->accessRights);

            // If role is 'user', grant all permissions while maintaining structure
            if ($role === 'user') {
                $accessRights = $this->setAllPermissionsTrue($accessRights);
            }

            $user->access_rights = $accessRights;
            unset($user->accessRights);

            $data = [
                'access_rights' => $accessRights,
            ];

            $data = api_successWithData('login user access rights', $accessRights);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Recursively set all permissions to true while maintaining structure. for login user
     */
    private function setAllPermissionsTrue(array $permissions): array
    {
        foreach ($permissions as $key => &$value) {
            if (is_array($value)) {
                $value = $this->setAllPermissionsTrue($value); // Recursively apply to nested arrays
            } else {
                $value = true; // Set leaf permissions to true
            }
        }
        return $permissions;
    }


    //for login user - account permission
    public function loginUserAccountPermissions(): JsonResponse
    {
        try {
            $user = request()->user();
            $id = $user->id;
            $role = $user->role;

            $user = $this->user->findById(
                $id,
                relations: [
                    'timeSlots',
                    'accountsPermission'
                ]
            );

            $accounts = ChartOfAccount::whereNull('parent_account_id')
                ->where('is_template', 1)
                ->orderBy('account_code')
                ->get();

            // Format the accounts recursively
            $defaultAccountPermissions = $this->formatAccounts($accounts);

            // If the role is "user", grant all permissions
            if ($role === 'user') {
                $defaultAccounts = $this->grantAllPermissions($defaultAccountPermissions);
            } else {
                $accountPermissions = $user->accountsPermission->pluck('granted', 'chart_of_account_code');
                $defaultAccounts = $this->formatAccountsWithPermissions($defaultAccountPermissions, $accountPermissions);
            }

            $user->accounts_permission = $defaultAccounts;
            unset($user->accountsPermission);

            $data = api_successWithData('login user account permissions', $defaultAccounts);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function grantAllPermissions(array $accounts): array
    {
        foreach ($accounts as &$account) {
            // Grant permission for the current account
            $account['granted'] = true;

            // Check if there are any children accounts to recurse into
            if (!empty($account['children']) && is_array($account['children'])) {
                // Recursively grant permissions to child accounts
                $account['children'] = $this->grantAllPermissions($account['children']);
            }
        }
        return $accounts;
    }
}
