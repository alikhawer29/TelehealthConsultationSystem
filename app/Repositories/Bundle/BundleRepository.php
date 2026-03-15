<?php

namespace App\Repositories\Bundle;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Package;
use App\Models\UserBranch;
use App\Models\Subscription;
use App\Models\UserTimeSlot;
use App\Core\Abstracts\Filters;
use App\Models\AccessManagement;
use App\Models\AccountsPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;

class BundleRepository extends BaseRepository implements BundleRepositoryContract

{
    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function create(array $params)
    {

        DB::beginTransaction();

        try {

            $user = request()->user();
            $businessId = $user->role === 'user' ? $user->id : $user->parent_id;

            // Get the owner's subscription details
            $subscription = Subscription::where(function ($q) use ($user) {
                $q->where('user_id', $user->parent_id)
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
            $maxUsers = $package->no_of_users;
            // Count the number of existing users under the current owner (parent_id)
            $existingUsers = User::where('parent_id', $businessId)->where('status', 1)->count();
            // Check if the number of existing users exceeds the maximum allowed
            if ($existingUsers >= $maxUsers) {
                throw new \Exception('You have reached the maximum number of users. To create a new user, you will need to upgrade your package.');
            }

            $result = $this->model->create([
                'user_name' => $params['user_name'],
                'email' => $params['email'],
                'country_code' => isset($params['country_code'], $params['phone']) ? $params['country_code'] : null,
                'phone' => isset($params['country_code'], $params['phone']) ? $params['phone'] : null,
                'password' => $params['password'],
                'role' => 'employee',
                'user_id' => $params['user_id'],
                'apply_time_restriction' => $params['apply_time_restriction'],
                'parent_id' => $businessId,
                'status' => 1,
            ]);

            // Create the time slots for the user if provided
            if (isset($params['time_slots']) && is_array($params['time_slots'])) {
                foreach ($params['time_slots'] as $slot) {
                    UserTimeSlot::create([
                        'user_id' => $result->id,
                        'day' => $slot['day'], // e.g., 'Monday', 'Tuesday'
                        'from' => $slot['from'], // e.g., '09:00'
                        'to' => $slot['to'], // e.g., '17:00'
                    ]);
                }
            }

            // Create the access rights for the user if provided
            if (!empty($params['access_rights']) && is_array($params['access_rights'])) {
                $accessData = [];
                $branchData = [];

                foreach ($params['access_rights'] as $parent => $modules) {
                    foreach ($modules as $module => $permissions) {
                        foreach ($permissions as $permission => $granted) {
                            $granted = filter_var($granted, FILTER_VALIDATE_BOOLEAN); // Normalize granted value

                            // Special handling for 'administration' and 'branch_selection'
                            if ($parent === 'administration' && $module === 'branch_selection') {
                                $branch = Branch::where('name', $permission)->first();
                                if ($branch) {
                                    $branchData[] = [
                                        'business_id' => $businessId,
                                        'employee_id' => $result->id,
                                        'branch_id' => $branch->id,
                                    ];
                                }
                            }

                            // Prepare access data for batch insert
                            $accessData[] = [
                                'business_id' => $businessId,
                                'employee_id' => $result->id,
                                'parent' => $parent,
                                'module' => $module,
                                'permission' => $permission,
                                'granted' => $granted,
                            ];
                        }
                    }
                }

                // Insert all branch data in a single query
                if (!empty($branchData)) {
                    UserBranch::insert($branchData);
                }

                // Insert all access data in a single query
                if (!empty($accessData)) {
                    AccessManagement::insert($accessData);
                }
            }

            // Create the accounts permissions for the user if provided
            if (isset($params['accounts_permission']) && is_array($params['accounts_permission'])) {
                foreach ($params['accounts_permission'] as $permission) {
                    foreach ($permission as $chartOfAccountId => $granted) {
                        AccountsPermission::create([
                            'business_id' => $businessId,
                            'employee_id' => $result->id,
                            'chart_of_account_code' => $chartOfAccountId,
                            'granted' => filter_var($granted, FILTER_VALIDATE_BOOLEAN), // Converts "true"/"false" to boolean
                        ]);
                    }
                }
            }

            DB::commit();
            return $result;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateUser($id, array $params)
    {
        DB::beginTransaction();

        try {
            $auth = request()->user();
            $businessId = $auth->role === 'user' ? $auth->id : $auth->parent_id;

            // Update the user data
            $user = $this->model->findOrFail($id);
            $user->update([
                'user_name' => $params['user_name'],
                'country_code' => $params['country_code'] ?? null,
                'phone' => $params['phone'] ?? null,
                'apply_time_restriction' => $params['apply_time_restriction'],
            ]);

            // Update the time slots for the user
            if (!empty($params['time_slots']) && is_array($params['time_slots'])) {
                UserTimeSlot::where('user_id', $id)->delete(); // Clear old time slots
                $timeSlotsData = array_map(function ($slot) use ($id) {
                    return [
                        'user_id' => $id,
                        'day' => $slot['day'],
                        'from' => $slot['from'],
                        'to' => $slot['to'],
                    ];
                }, $params['time_slots']);
                UserTimeSlot::insert($timeSlotsData); // Batch insert time slots
            }

            // Update the access rights for the user
            if (!empty($params['access_rights']) && is_array($params['access_rights'])) {
                $accessData = [];
                $branchData = [];
                AccessManagement::where('employee_id', $id)->delete(); // Clear old access rights
                UserBranch::where('employee_id', $id)->delete(); // Clear old branch data

                foreach ($params['access_rights'] as $parent => $modules) {
                    foreach ($modules as $module => $permissions) {
                        foreach ($permissions as $permission => $granted) {
                            $granted = filter_var($granted, FILTER_VALIDATE_BOOLEAN);

                            // Special handling for 'administration' and 'branch_selection'
                            if ($parent === 'administration' && $module === 'branch_selection') {
                                $branch = Branch::where('name', $permission)->first();
                                if ($branch) {
                                    $branchData[] = [
                                        'business_id' => $businessId,
                                        'employee_id' => $id,
                                        'branch_id' => $branch->id,
                                    ];
                                }
                            }

                            // Collect access rights data
                            $accessData[] = [
                                'business_id' => $businessId,
                                'employee_id' => $id,
                                'parent' => $parent,
                                'module' => $module,
                                'permission' => $permission,
                                'granted' => $granted,
                            ];
                        }
                    }
                }

                // Batch insert access rights and branch data
                if (!empty($accessData)) {
                    AccessManagement::insert($accessData);
                }
                if (!empty($branchData)) {
                    UserBranch::insert($branchData);
                }
            }

            // Update the accounts permissions for the user
            if (!empty($params['accounts_permission']) && is_array($params['accounts_permission'])) {
                AccountsPermission::where('employee_id', $id)->delete(); // Clear old permissions
                $accountsPermissionData = [];

                foreach ($params['accounts_permission'] as $permissions) {
                    foreach ($permissions as $chartOfAccountId => $granted) {
                        $accountsPermissionData[] = [
                            'business_id' => $businessId,
                            'employee_id' => $id,
                            'chart_of_account_code' => $chartOfAccountId,
                            'granted' => filter_var($granted, FILTER_VALIDATE_BOOLEAN),
                        ];
                    }
                }

                // Batch insert account permissions
                if (!empty($accountsPermissionData)) {
                    AccountsPermission::insert($accountsPermissionData);
                }
            }

            DB::commit();
            return $user;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }



    public function status($id)
    {
        try {
            $user = request()->user();
            $data = $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 1 THEN 0 ELSE 1 END")
                ]);

            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function totalUsers(Filters|null $filter = null)
    {
        try {

            $totalUsers = $this->model->filter($filter)->where('role', 'user')->count();
            return  $totalUsers;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalEarnings(Filters|null $filter = null)
    {
        try {
            // Apply filters if provided
            $query = Subscription::query();

            if ($filter) {
                $query = $filter->apply($query);
            }

            $totalEarnings = $query->with('package') // Eager load the related package
                ->get()
                ->sum(function ($subscription) {
                    // Calculate the price based on the type of subscription
                    if ($subscription->type === 'yearly') {
                        return $subscription?->package?->price_yearly;
                    } elseif ($subscription->type === 'monthly') {
                        return $subscription?->package?->price_monthly;
                    }
                    return 0; // Default to 0 if the type is unknown
                });

            return $totalEarnings;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


 

    public function getLapsAccount(Filters|null $filter = null)
    {
        try {
            $oneWeekAgo = Carbon::now()->subWeek(); // One week ago from now
            $now = Carbon::now(); // Current time

            // Total users count
            $totalUsers = $this->model->filter($filter)->count();

            // Count users whose `updated_at` was not updated in the last week
            $inactiveUsers = $this->model
                ->filter($filter)
                ->where('updated_at', '<', $oneWeekAgo) // Last updated before one week ago
                ->count();

            return ['total' => $inactiveUsers, 'inactive' => $inactiveUsers];
        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function getTotalCount(Filters|null $filter = null)
    {
        try {
            $oneWeekAgo = Carbon::now()->subWeek();
            $now = Carbon::now();
            $totalUsers = $this->model->filter($filter)->count();
            $lastWeekUsers = $this->model->filter($filter)->whereBetween('created_at', [$oneWeekAgo, $now])->count();
            if ($totalUsers > 0) {
                $percentageLastWeek = ($lastWeekUsers / $totalUsers) * 100;
            } else {
                // Handle the case where there are no orders to avoid division by zero
                $percentageLastWeek = 0;
            }

            return  ['total' => $totalUsers, 'trend' => $percentageLastWeek];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
