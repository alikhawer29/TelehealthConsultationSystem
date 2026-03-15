<?php

namespace App\Repositories\User;

use Carbon\Carbon;
use App\Models\Slot;
use App\Models\User;
use App\Models\Media;
use App\Models\Branch;
use App\Models\License;
use App\Models\Package;
use App\Models\Education;
use App\Models\UserBranch;
use App\Models\Appointment;
use App\Models\SessionType;
use App\Models\Subscription;
use App\Models\UserTimeSlot;
use App\Services\ZohoService;
use App\Core\Abstracts\Filters;
use App\Models\ReminderSetting;
use App\Models\AccessManagement;
use App\Models\AccountsPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Mail\HealthcareUserCredentialsMail;
use App\Core\Abstracts\Repository\BaseRepository;

class UserRepository extends BaseRepository implements UserRepositoryContract

{
    protected $model;
    protected $slots;
    protected $zohoService;

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->slots = new Slot();
    }

    public function __construct(ZohoService $zohoService)
    {
        $this->zohoService = $zohoService;
    }


    public function createHealthCare(array $params)
    {
        DB::beginTransaction();

        try {
            $roles = [
                'Consultant' => 'doctor',
                'Nurse' => 'nurse',
                'Physician' => 'physician',
            ];

            $params['role'] = $roles[$params['professional']] ?? null;
            $params['status'] = 1;

            $result = $this->model->create($params);

            ReminderSetting::create([
                'user_id' => $result->id,
                'user_type' => $roles[$params['professional']],
                'reminder_time' => '5_min',
                'reference_id' => $result->id
            ]);

            $license = null;
            if (isset($params['license'])) {
                $license = License::create([
                    'user_id' => $result->id,
                    'authroity' => $params['license']['authroity'] ?? null,
                    'number' => $params['license']['number'] ?? null,
                    'expiry' => $params['license']['expiry'] ?? null,
                    'specialty' => $params['license']['specialty'] ?? null,
                ]);
            }

            // Handle education
            if (isset($params['education'])) {
                foreach ($params['education'] as $education) {
                    Education::create([
                        'user_id' => $result->id,
                        'institution_name' => $education['institution_name'],
                        'degree_title' => $education['degree_title'],
                    ]);
                }
            }

            // Handle session types
            if (isset($params['session_type'])) {
                foreach ($params['session_type'] as $session) {
                    SessionType::create([
                        'user_id' => $result->id,
                        'session_type' => $session['type'],
                        'price' => $session['price'],
                    ]);
                }
            }

            // Handle license file upload
            if (isset($params['license']['license_file']) && $license) {
                $uploadedFile = $params['license']['license_file'];

                // Check if it's a valid file upload
                if ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                    $path = $this->uploadFile($uploadedFile);
                    $this->storeFile($path, $uploadedFile, $license->id, License::class);
                }
            }

            $zohoData = [
                'first_name'  => $result->first_name,
                'last_name'  => $result->last_name,
                'email' => $result->email,
                'role'  => $result->role,
                'id'  => $result->id,
            ];

            $titleMap = [
                'doctor' => 'Dr.',
                'nurse' => 'Healthcare Professional',
                'physician' => 'Healthcare Professional',
            ];

            $professional = strtolower($params['professional'] ?? '');
            $title = $titleMap[$professional] ?? 'Healthcare Professional';

            // Send email with credentials
            Mail::to($params['email'])->send(
                new HealthcareUserCredentialsMail(
                    $title . ' ' . $params['first_name'] ?? 'User',
                    $params['email'],
                    $params['password']
                )
            );

            DB::commit();
            return $result;
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('Error creating healthcare professional: ' . $th->getMessage());
            \Log::error($th->getTraceAsString());
            throw $th;
        }
    }

    protected function uploadFile($file)
    {
        // Generate a unique filename
        $filename = time() . '_' . $file->getClientOriginalName();

        // Store the file in the storage/app/public/media directory
        $path = $file->storeAs('public/media', $filename);

        return $path;
    }

    protected function storeFile($path, $file, $fileableId, $fileableType)
    {
        return Media::create([
            'path' => $path,
            'field_name' => 'license_file',
            'name' => $file->getClientOriginalName(),
            'fileable_type' => $fileableType,
            'fileable_id' => $fileableId,
        ]);
    }

    public function updateHealthCare($id, array $params)
    {
        DB::beginTransaction();

        try {
            $roles = [
                'Consultant' => 'doctor',
                'Nurse' => 'nurse',
                'Physician' => 'physician',
            ];

            // Find the existing record
            $healthcare = $this->model->findOrFail($id);

            // Update role if professional is being changed
            if (isset($params['professional']) && array_key_exists($params['professional'], $roles)) {
                $params['role'] = $roles[$params['professional']];
            }

            // Update the record
            $healthcare->update($params);

            // Update reminder setting if role changed
            if (isset($params['professional'])) {
                $reminderSetting = ReminderSetting::where('reference_id', $id)->first();

                if ($reminderSetting) {
                    $reminderSetting->update([
                        'user_type' => $roles[$params['professional']] ?? $healthcare->role
                    ]);
                } else {
                    // Create if doesn't exist (for backward compatibility)
                    ReminderSetting::create([
                        'user_type' => $roles[$params['professional']] ?? $healthcare->role,
                        'reminder_time' => '5_min',
                        'reference_id' => $id
                    ]);
                }
            }

            $license = null;
            if (isset($params['license'])) {
                $license = License::updateOrCreate(['user_id' => $id], [
                    'authroity' => $params['license']['authroity'] ?? null,
                    'number' => $params['license']['number'] ?? null,
                    'expiry' => $params['license']['expiry'] ?? null,
                    'specialty' => $params['license']['specialty'] ?? null,
                ]);
            }

            // Handle education
            if (isset($params['education'])) {
                Education::where('user_id', $id)->delete();

                foreach ($params['education'] as $education) {
                    Education::create([
                        'user_id' => $id,
                        'institution_name' => $education['institution_name'],
                        'degree_title' => $education['degree_title'],
                    ]);
                }
            }

            // Handle session types
            if (isset($params['session_type'])) {
                SessionType::where('user_id', $id)->delete();
                foreach ($params['session_type'] as $session) {
                    SessionType::create([
                        'user_id' => $id,
                        'session_type' => $session['type'],
                        'price' => $session['price'],
                    ]);
                }
            }

            // Handle license file upload
            if (isset($params['license']['license_file']) && $license) {
                $uploadedFile = $params['license']['license_file'];

                // Check if it's a valid file upload
                if ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                    $path = $this->uploadFile($uploadedFile);
                    $this->storeFile($path, $uploadedFile, $license->id, License::class);
                }
            }


            // Update Zoho data if needed
            /*
            if ($healthcare->zoho_id) {
                $zohoData = [
                    'first_name' => $healthcare->first_name,
                    'last_name' => $healthcare->last_name,
                    'email' => $healthcare->email,
                    'role' => $healthcare->role,
                    'id' => $healthcare->zoho_id // Use zoho_id for update
                ];

                // $response = $this->zohoService->updateOperator($healthcare->zoho_id, $zohoData);
                // \Log::info('Zoho update response:', $response);
            }
            */
            $professional = strtolower($params['professional'] ?? '');
            $title = $titleMap[$professional] ?? 'Healthcare Professional';
            // If email is being updated and password is provided, send credentials
            if ((isset($params['email']) && $params['email'] !== $healthcare->email) || isset($params['password'])) {
                Mail::to($params['email'] ?? $healthcare->email)->send(
                    new HealthcareUserCredentialsMail(
                        $title . ' ' . $params['first_name'] ?? $healthcare->first_name,
                        $params['email'] ?? $healthcare->email,
                        $params['password'] ?? 'Your existing password' // Handle password appropriately
                    )
                );
            }

            DB::commit();
            return $healthcare->fresh(); // Return refreshed instance
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }





    public function status($id)
    {
        try {
            // $user = request()->user();
            $data = $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 1 THEN 0 ELSE 1 END")
                ]);

            $updatedUser = $this->model->findOrFail($id); // Fetch updated user data


            // Send notification with new status
            $this->notification()->send(
                $updatedUser,
                title: 'Profile Status',
                body: "Your profile status has been changed to " . ($updatedUser->status ? 'Active' : 'Inactive'),
                sound: 'customSound',
                id: $id,
                data: [
                    'id'     => $updatedUser->id,
                    'type'   => 'profile-status',
                    'status' => $updatedUser->status, // Include new status in notification
                    'sound'  => 'customSound',
                ]
            );

            return $updatedUser;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function totalUsers(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalUsers = $this->model->filter($filter)->where('role', 'user')->count();
            $currentMonthUsers = $this->model->whereMonth('created_at', $currentMonth)->count();
            $previousMonthUsers = $this->model->whereMonth('created_at', $previousMonth)->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthUsers > $previousMonthUsers;
            $difference = abs($currentMonthUsers - $previousMonthUsers); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthUsers > 0
                ? (($difference / $previousMonthUsers) * 100)
                : ($currentMonthUsers > 0 ? 100 : 0); // If no users in previous month, set to 100% if new users exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalUsers,
                'increase' => $increase, // True if current month users are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function totalConsultants(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalUsers = $this->model->filter($filter)->where('role', 'doctor')->count();
            $currentMonthUsers = $this->model->whereMonth('created_at', $currentMonth)->count();
            $previousMonthUsers = $this->model->whereMonth('created_at', $previousMonth)->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthUsers > $previousMonthUsers;
            $difference = abs($currentMonthUsers - $previousMonthUsers); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthUsers > 0
                ? (($difference / $previousMonthUsers) * 100)
                : ($currentMonthUsers > 0 ? 100 : 0); // If no users in previous month, set to 100% if new users exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalUsers,
                'increase' => $increase, // True if current month users are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function totalNurses(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalUsers = $this->model->filter($filter)->where('role', 'nurse')->count();
            $currentMonthUsers = $this->model->whereMonth('created_at', $currentMonth)->count();
            $previousMonthUsers = $this->model->whereMonth('created_at', $previousMonth)->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthUsers > $previousMonthUsers;
            $difference = abs($currentMonthUsers - $previousMonthUsers); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthUsers > 0
                ? (($difference / $previousMonthUsers) * 100)
                : ($currentMonthUsers > 0 ? 100 : 0); // If no users in previous month, set to 100% if new users exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalUsers,
                'increase' => $increase, // True if current month users are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function totalPhysicians(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalUsers = $this->model->filter($filter)->where('role', 'physician')->count();
            $currentMonthUsers = $this->model->whereMonth('created_at', $currentMonth)->count();
            $previousMonthUsers = $this->model->whereMonth('created_at', $previousMonth)->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthUsers > $previousMonthUsers;
            $difference = abs($currentMonthUsers - $previousMonthUsers); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthUsers > 0
                ? (($difference / $previousMonthUsers) * 100)
                : ($currentMonthUsers > 0 ? 100 : 0); // If no users in previous month, set to 100% if new users exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalUsers,
                'increase' => $increase, // True if current month users are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
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

    public function slots1($id, $params)
    {
        try {
            $owner = $this->model->find($id);
            if (!$owner) {
                throw new \Exception("User not found for ID: $id");
            }
            $owner_class = get_class($owner);
            $weekDay = $params ? Carbon::parse($params)->dayOfWeek : null;

            // Get booked slot IDs for the selected specific date only (Convert to array)
            $bookedSlots = Appointment::when($params, function ($q) use ($params, $id) {
                return $q->whereDate('appointment_date', $params)
                    ->where('bookable_type', 'App\Models\User')
                    ->where('bookable_id', $id)
                    ->where('status', '!=', 'cancelled')
                    ->where('status', '!=', 'pending');
            })
                ->pluck('slot_id')
                ->toArray();

            // Get all slots available on this weekday (Monday slots)
            $get = $this->slots
                ->where('reference_type', $owner_class)
                ->where('reference_id', $owner->id)
                ->where('modification_status', 'active')
                ->where('status', 1)
                ->where('slot_type', 'doctor')
                ->when($weekDay !== null, function ($q) use ($weekDay) {
                    $q->where('day', $weekDay);
                })
                ->orderBy('day')
                ->get(['id', 'index', 'day_name', 'start_time', 'end_time', 'day']) // Exclude booking_status from DB
                ->map(function ($slot) use ($bookedSlots) {
                    // Override booking_status dynamically
                    $slot->booking_status = in_array($slot->id, $bookedSlots) ? "1" : "0";
                    return $slot;
                });

            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    //user doctor
    public function slots($id, $params)
    {
        try {
            $owner = $this->model->find($id);
            if (!$owner) {
                throw new \Exception("User not found for ID: $id");
            }

            $owner_class = get_class($owner);
            $carbonDate = $params ? Carbon::parse($params) : null;
            $weekDay = $carbonDate?->dayOfWeek;

            // Get all valid appointments for the date
            $appointments = Appointment::whereNotIn('status', ['cancelled', 'pending'])
                ->where('appointment_status', '!=', 'completed')
                ->where(function ($query) use ($carbonDate) {
                    $query->where(function ($q) use ($carbonDate) {
                        $q->where('status', 'scheduled')
                            ->whereDate('appointment_date', $carbonDate);
                    })->orWhere(function ($q) use ($carbonDate) {
                        $q->where('status', 'requested')
                            ->whereDate('request_date', $carbonDate);
                    });
                })
                ->get([
                    'status',
                    'appointment_start_time',
                    'appointment_end_time',
                    'request_start_time',
                    'request_end_time'
                ]);

            // Get slots
            $slots = $this->slots
                ->where('reference_type', $owner_class)
                ->where('reference_id', $owner->id)
                ->where('modification_status', 'active')
                ->where('status', 1)
                ->where('slot_type', 'doctor')
                ->when($weekDay !== null, function ($q) use ($weekDay) {
                    $q->where('day', $weekDay);
                })
                ->orderBy('day')
                ->get(['id', 'index', 'day_name', 'start_time', 'end_time', 'day']);

            // Match slots with appointment times
            $slots = $slots->map(function ($slot) use ($appointments) {
                $isBooked = $appointments->contains(function ($appointment) use ($slot) {
                    if ($appointment->status === 'requested') {
                        return $appointment->request_start_time === $slot->start_time &&
                            $appointment->request_end_time === $slot->end_time;
                    } elseif ($appointment->status === 'scheduled') {
                        return $appointment->appointment_start_time === $slot->start_time &&
                            $appointment->appointment_end_time === $slot->end_time;
                    }
                    return false;
                });

                $slot->booking_status = $isBooked ? "1" : "0";
                return $slot;
            });

            return $slots;
        } catch (\Throwable $th) {
            throw $th;
        }
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
}
