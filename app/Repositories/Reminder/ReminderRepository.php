<?php

namespace App\Repositories\Reminder;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Package;
use App\Models\UserBranch;
use App\Models\Appointment;
use App\Models\ServiceSlot;
use App\Models\Subscription;
use App\Models\UserTimeSlot;
use App\Core\Abstracts\Filters;
use App\Models\AccessManagement;
use App\Models\AccountsPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Models\BundleService;
use App\Models\Slot;

class ReminderRepository extends BaseRepository implements ReminderRepositoryContract

{
    protected $model;
    protected $slots;


    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->slots = new Slot();
    }


    public function slots($id, $params)
    {
        try {
            $owner = $this->model->findorfail($id);
            $owner_class = get_class($owner);
            $weekDay = Carbon::parse($params)->dayOfWeek; // Get weekday from date

            // Get booked slot IDs for the selected specific date only (Convert to array)
            $bookedSlots = Appointment::whereDate('appointment_date', $params)
                ->where('status', '!=', 'pending') // Only exclude active bookings
                ->where('service_type', 'homecare')
                ->pluck('slot_id')
                ->toArray(); // ✅ Convert to an array to avoid TypeError

            // 🔹 Default behavior for Homecare & Doctor (linked to reference_id)
            $get = $this->slots
                ->where('reference_type', $owner_class)
                ->where('reference_id', $owner->id)
                ->where('status', 1)
                ->where('slot_type', 'homecare_service')
                ->when($params, function ($q) use ($weekDay) {
                    $q->where('day', $weekDay);
                })
                ->orderBy('day')
                ->get(['id', 'index', 'day_name', 'start_time', 'end_time', 'day']) // Exclude booking_status from DB
                ->map(function ($slot) use ($bookedSlots) {
                    // 🔹 Override booking_status dynamically
                    $slot->booking_status = in_array($slot->id, $bookedSlots) ? "1" : "0";
                    return $slot;
                });

            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function labSlots($params)
    {
        try {
            $weekDay = Carbon::parse($params)->dayOfWeek; // Get weekday from date

            $get = $this->slots
                ->where('slot_type', 'lab_service')
                ->where('status', 1)
                ->when($params, function ($q) use ($weekDay) {
                    $q->where('day', $weekDay);
                })
                ->orderBy('day')
                ->get(['id', 'index', 'day_name', 'start_time', 'end_time', 'day']) // Exclude booking_status from DB
                ->map(function ($slot) use ($params) {
                    // 🔹 Check if any appointment exists for this lab service on this date/time
                    $isBooked = Appointment::whereDate('appointment_date', $params)
                        ->whereTime('appointment_start_time', '=', $slot->start_time) // Match exact time
                        ->where('status', '!=', 'pending')
                        ->whereIn('service_type', ['lab', 'lab_bundle'])
                        ->exists(); // ✅ Check if a record exists

                    // 🔹 Override booking_status dynamically
                    $slot->booking_status = $isBooked ? "1" : "0";
                    return $slot;
                });

            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function createReminder($role, $params)
    {
        DB::beginTransaction();

        try {
            $userId = request()->user()->id;

            $result = $this->model->updateOrCreate(
                [
                    'user_type' => $role,
                    'reference_id' => $userId, // Ensures a unique record per user role
                ],
                [
                    'reminder_time' => $params['reminder_time'],
                    'custom_time' => $params['reminder_time'] === 'custom' ? $params['custom_time'] : null, // Null for non-custom times
                ]
            );

            DB::commit();
            return $result;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }



    public function updateService($id, array $params)
    {
        DB::beginTransaction();

        try {

            // Find the service
            $service = $this->model->findOrFail($id);

            // Update service details
            $service->update([
                'name'        => $params['name'],
                'description' => $params['description'],
                'price'       => $params['price'],
                'type'        => $params['type'],
                'status'      => $params['status'],
            ]);

            $type = $params['type'] === 'homecare' ? 'homecare_service' : 'lab_service';

            // Remove existing slots for this service
            Slot::where('reference_type', 'App\Models\Service')
                ->where('reference_id', $id)
                ->delete();

            // Process and insert new slots if provided
            if (!empty($params['slots']) && is_array($params['slots'])) {
                foreach ($params['slots'] as $day => $slotData) {
                    if (isset($slotData['status']) && is_array($slotData['times'])) {
                        foreach ($slotData['times'] as $timing) {
                            if (!empty($timing['start_time']) && !empty($timing['end_time'])) {
                                $dayNumber = date('N', strtotime($day)); // 1 (Monday) to 7 (Sunday)
                                $index = $dayNumber - 1; // Convert to 0-based index

                                Slot::create([
                                    'slot_type'       => $type,
                                    'reference_type'  => 'App\Models\Service',
                                    'reference_id'    => $service->id,
                                    'day'             => $dayNumber,
                                    'day_name'        => $day,
                                    'index'           => $index,
                                    'start_time'      => $timing['start_time'],
                                    'end_time'        => $timing['end_time'],
                                    'status'          => (int) $slotData['status'],
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return $service;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function createBundle(array $params): Model
    {
        DB::beginTransaction();
        try {
            // Create the bundle
            $bundle = $this->model->create([
                ...$params,
                'type' => 'lab_bundle',
            ]);

            // Bulk insert services if provided
            if (!empty($params['services']) && is_array($params['services'])) {
                $bundleServices = collect($params['services'])->map(fn($serviceId) => [
                    'bundle_id' => $bundle->id,
                    'service_id' => $serviceId,
                    'created_at' => now(),
                ])->toArray();

                BundleService::insert($bundleServices);
            }

            DB::commit();

            // Return bundle with related services
            return $bundle->load('services');
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateBundle($id, array $params): Model
    {
        DB::beginTransaction();
        try {
            // Find the existing bundle
            $bundle = $this->model->findOrFail($id);

            // Update bundle details
            $bundle->update([
                'bundle_name' => $params['bundle_name'] ?? $bundle->bundle_name,
                'description' => $params['description'] ?? $bundle->description,
                'price'       => $params['price'] ?? $bundle->price,
                'status'      => $params['status'] ?? $bundle->status,
                'file'        => $params['file'] ?? $bundle->file,
            ]);

            // Sync services if provided
            if (!empty($params['services']) && is_array($params['services'])) {
                $bundle->services()->sync($params['services']);
            }

            DB::commit();

            // Return the updated bundle with related services
            return $bundle->load('services');
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
                    'status' => \DB::raw("CASE WHEN status = '1' THEN '0' ELSE '1' END")
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
