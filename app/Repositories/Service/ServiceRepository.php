<?php

namespace App\Repositories\Service;

use Carbon\Carbon;
use App\Models\Slot;
use App\Models\User;
use App\Models\Media;
use App\Models\Branch;
use App\Models\Package;
use App\Models\UserBranch;
use App\Models\Appointment;
use App\Models\ServiceSlot;
use App\Models\Subscription;
use App\Models\UserTimeSlot;
use App\Models\BundleService;
use App\Core\Abstracts\Filters;
use App\Models\AccessManagement;
use App\Models\AccountsPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Core\Abstracts\Repository\BaseRepository;

class ServiceRepository extends BaseRepository implements ServiceRepositoryContract

{
    protected $model;
    protected $slots;


    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->slots = new Slot();
    }

    //for users service
    public function slots($id, $params)
    {
        try {
            // $owner = $this->model->find($id);
            // if (!$owner) {
            //     throw new \Exception("User not found");
            // }

            // $owner_class = get_class($owner);
            $carbonDate = $params ? Carbon::parse($params) : null;
            $weekDay = $carbonDate?->dayOfWeek;

            // Get all valid appointments for the date
            $appointments = Appointment::whereNotIn('status', ['cancelled', 'pending'])
                ->where('appointment_status', '!=', 'completed')
                ->whereIn('service_type', ['homecare', 'nursing_care'])
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
                // ->where('reference_type', $owner_class)
                // ->where('reference_id', $owner->id)
                ->where('modification_status', 'active')
                ->where('status', 1)
                // ->where('slot_type', 'homecare_service')
                ->whereIn('slot_type', ['homecare_service', 'nursing_care'])

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

    public function slotsNew($date, $type)
    {
        try {
            $serviceType = $type === 'homecare' ? 'homecare_service' : $type;
            $carbonDate = $date ? Carbon::parse($date) : null;
            $weekDay = $carbonDate?->dayOfWeek;

            // Get all valid appointments for the date
            $appointments = Appointment::whereNotIn('status', ['cancelled', 'pending'])
                ->where('appointment_status', '!=', 'completed')
                ->where('service_type', $type)
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
                ->where('modification_status', 'active')
                ->where('status', 1)
                ->where('slot_type', $serviceType)

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

    public function labSlots($params)
    {
        try {
            $weekDay = Carbon::parse($params)->dayOfWeek; // Get weekday from date

            $get = $this->slots
                ->where('slot_type', 'lab_service')
                ->where('modification_status', 'active')
                ->where('status', 1)
                ->when($params, function ($q) use ($weekDay) {
                    $q->where('day', $weekDay);
                })
                ->orderBy('day')
                ->get(['id', 'index', 'day_name', 'start_time', 'end_time', 'day']) // Exclude booking_status from DB
                ->map(function ($slot) use ($params) {
                    $isBooked = Appointment::whereIn('service_type', ['lab', 'lab_bundle', 'lab_custom'])
                        ->where(function ($query) use ($params, $slot) {
                            $query->where(function ($q) use ($params, $slot) {
                                $q->where('status', 'requested')
                                    ->whereDate('request_date', $params)
                                    ->whereTime('request_start_time', $slot->start_time);
                            })->orWhere(function ($q) use ($params, $slot) {
                                $q->where('status', 'scheduled')
                                    ->whereDate('appointment_date', $params)
                                    ->whereTime('appointment_start_time', $slot->start_time);
                            });
                        })
                        ->where('appointment_status', '!=', 'completed')
                        ->whereNotIn('status', ['pending', 'cancelled'])
                        ->exists();


                    // 🔹 Override booking_status dynamically
                    $slot->booking_status = $isBooked ? "1" : "0";
                    return $slot;
                });

            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function ivDripSlots($params)
    {
        try {
            $weekDay = Carbon::parse($params)->dayOfWeek; // Get weekday from date

            $get = $this->slots
                ->where('slot_type', 'iv_drip')
                ->where('modification_status', 'active')
                ->where('status', 1)
                ->when($params, function ($q) use ($weekDay) {
                    $q->where('day', $weekDay);
                })
                ->orderBy('day')
                ->get(['id', 'index', 'day_name', 'start_time', 'end_time', 'day']) // Exclude booking_status from DB
                ->map(function ($slot) use ($params) {
                    $isBooked = Appointment::where('service_type', 'iv_drip')
                        ->where(function ($query) use ($params, $slot) {
                            $query->where(function ($q) use ($params, $slot) {
                                $q->where('status', 'requested')
                                    ->whereDate('request_date', $params)
                                    ->whereTime('request_start_time', $slot->start_time);
                            })->orWhere(function ($q) use ($params, $slot) {
                                $q->where('status', 'scheduled')
                                    ->whereDate('appointment_date', $params)
                                    ->whereTime('appointment_start_time', $slot->start_time);
                            });
                        })
                        ->where('appointment_status', '!=', 'completed')
                        ->whereNotIn('status', ['pending', 'cancelled'])
                        ->exists();


                    // 🔹 Override booking_status dynamically
                    $slot->booking_status = $isBooked ? "1" : "0";
                    return $slot;
                });

            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /*
        for appointment types will be:
        1- doctor
        2- homecare
        3- lab
        4- iv_drip
        5- nursing_care
        6- lab_custom
        7- iv_drip_custom
        8- lab_bundle

        for slots types will be:
        1- doctor
        2- homecare_service
        3- lab_service
        4- iv_drip
        5- nursing_care

        for bundle types will be:
        1- lab_custom
        2- iv_drip_custom
        3- lab_bundle

        */

    public function slotsForUser($id = null, $date = null, $type = 'homecare')
    {
        try {
            $carbonDate = $date ? Carbon::parse($date) : now();
            $weekDay = $carbonDate->dayOfWeek;

            $slotTypeMap = [
                'homecare'     => ['homecare_service', 'nursing_care'],
                'lab'          => ['lab_service'],
                'iv_drip'      => ['iv_drip'],
            ];

            if (!isset($slotTypeMap[$type])) {
                throw new \Exception("Invalid service type: $type");
            }

            $slotTypes = $slotTypeMap[$type];

            // Only homecare/nursing_care requires reference_id filtering
            $slotsQuery = $this->slots
                ->where('modification_status', 'active')
                ->where('status', 1)
                ->whereIn('slot_type', $slotTypes)
                ->when($weekDay !== null, fn($q) => $q->where('day', $weekDay));

            if ($type === 'homecare' && $id) {
                $owner = $this->model->find($id);
                if (!$owner) {
                    throw new \Exception("User not found for ID: $id");
                }

                $slotsQuery->where('reference_type', get_class($owner))
                    ->where('reference_id', $owner->id);
            }

            $slots = $slotsQuery->orderBy('day')->get([
                'id',
                'index',
                'day_name',
                'start_time',
                'end_time',
                'day'
            ]);

            // Appointment types to match with
            $appointmentTypes = [
                'homecare' => ['homecare', 'nursing_care'],
                'lab' => ['lab', 'lab_bundle', 'lab_custom'],
                'iv_drip' => ['iv_drip'],
            ];

            $appointments = Appointment::whereIn('service_type', $appointmentTypes[$type])
                ->where('appointment_status', '!=', 'completed')
                ->whereNotIn('status', ['cancelled', 'pending'])
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

            // Mark booking status for each slot
            $slots->transform(function ($slot) use ($appointments) {
                $isBooked = $appointments->contains(function ($appt) use ($slot) {
                    if ($appt->status === 'requested') {
                        return $appt->request_start_time === $slot->start_time &&
                            $appt->request_end_time === $slot->end_time;
                    } elseif ($appt->status === 'scheduled') {
                        return $appt->appointment_start_time === $slot->start_time &&
                            $appt->appointment_end_time === $slot->end_time;
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
            $userId = request()->user()->id;

            $type = $params['type'];

            $commonFields = [
                'name' => $params['name'],
                'price' => $params['price'],
                'type' => $type,
                'created_by' => $userId,
                'status' => $params['status'],
            ];

            switch ($type) {
                case 'homecare': // Physiotherapy
                    $typeSpecific = [
                        'about' => $params['about'],
                        'what_to_expect_during_the_sessions' => $params['what_to_expect_during_the_sessions'],
                        'preparations_and_precautions' => $params['preparations_and_precautions'],
                        'who_should_consider_this_service' => $params['who_should_consider_this_service'],
                        'conditions_to_treat' => $params['conditions_to_treat'],
                    ];
                    break;

                case 'lab': // Lab Services
                    $typeSpecific = [
                        'about' => $params['about'],
                        'parameters_included' => $params['parameters_included'],
                        'precautions' => $params['precautions'],
                        'fasting_requirments' => $params['fasting_requirments'],
                        'turnaround_time' => $params['turnaround_time'],
                        'when_to_get_tested' => $params['when_to_get_tested'],
                    ];
                    break;

                case 'nursing_care': // Nursing Care
                    $typeSpecific = [
                        'about' => $params['about'],
                        'what_to_expect_during_the_sessions' => $params['what_to_expect_during_the_sessions'],
                        'preparations_and_precautions' => $params['preparations_and_precautions'],
                        'who_should_consider_this_service' => $params['who_should_consider_this_service'],
                        'conditions_to_treat' => $params['conditions_to_treat'],
                    ];
                    break;

                case 'iv_drip': // IV Drip Services
                    $typeSpecific = [
                        'general_information' => $params['general_information'],
                        'ingredients' => $params['ingredients'],
                        'preparations' => $params['preparations'],
                        'administration_time' => $params['administration_time'],
                        'restriction' => $params['restriction'],
                    ];
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported type: $type");
            }

            $result = $this->model->create(array_merge($commonFields, $typeSpecific));

            $type = $params['type'] === 'homecare' ?
                'homecare_service' : ($params['type'] === 'lab' ?
                    'lab_service' : ($params['type'] === 'iv_drip' ?
                        'iv_drip_service' : ($params['type'] === 'nursing_care' ?
                            'nursing_care' : $params['type'])));


            // Process Slots if provided
            if (!empty($params['slots']) && is_array($params['slots'])) {
                foreach ($params['slots'] as $day => $slotData) {
                    if (isset($slotData['status']) && is_array($slotData['times'])) {
                        foreach ($slotData['times'] as $timing) {
                            if (isset($timing['start_time'], $timing['end_time'])) {
                                $dayNumber = date('N', strtotime($day)); // 1 (Monday) to 7 (Sunday)
                                $index = $dayNumber - 1; // Convert to 0-based index (Monday = 0, Sunday = 6)
                                Slot::create([
                                    'slot_type'       => $type,
                                    'reference_type'  => 'App\Models\Service',
                                    'reference_id'    => $result->id,
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


            if (isset($params['icon'])) {
                $path = $this->uploadFile($params['icon']);
                $file = $params['icon'];
                $type = 'icon';
                $path = $this->storeFile($path, $file, $result->id, $type);
            }

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

            $type = $params['type'];

            $commonFields = [
                'name' => $params['name'],
                'price' => $params['price'],
                'type' => $type,
                'status' => $params['status'],
            ];

            switch ($type) {
                case 'homecare': // Physiotherapy
                    $typeSpecific = [
                        'about' => $params['about'],
                        'what_to_expect_during_the_sessions' => $params['what_to_expect_during_the_sessions'],
                        'preparations_and_precautions' => $params['preparations_and_precautions'],
                        'who_should_consider_this_service' => $params['who_should_consider_this_service'],
                        'conditions_to_treat' => $params['conditions_to_treat'],
                    ];
                    break;

                case 'lab': // Lab Services
                    $typeSpecific = [
                        'about' => $params['about'],
                        'parameters_included' => $params['parameters_included'],
                        'precautions' => $params['precautions'],
                        'fasting_requirments' => $params['fasting_requirments'],
                        'turnaround_time' => $params['turnaround_time'],
                        'when_to_get_tested' => $params['when_to_get_tested'],
                    ];
                    break;

                case 'nursing_care': // Nursing Care
                    $typeSpecific = [
                        'about' => $params['about'],
                        'what_to_expect_during_the_sessions' => $params['what_to_expect_during_the_sessions'],
                        'preparations_and_precautions' => $params['preparations_and_precautions'],
                        'who_should_consider_this_service' => $params['who_should_consider_this_service'],
                        'conditions_to_treat' => $params['conditions_to_treat'],
                    ];
                    break;

                case 'iv_drip': // IV Drip Services
                    $typeSpecific = [
                        'general_information' => $params['general_information'],
                        'ingredients' => $params['ingredients'],
                        'preparations' => $params['preparations'],
                        'administration_time' => $params['administration_time'],
                        'restriction' => $params['restriction'],
                    ];
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported type: $type");
            }

            // Update service details
            $service->update(array_merge($commonFields, $typeSpecific));




            $type = $params['type'] === 'homecare' ?
                'homecare_service' : ($params['type'] === 'lab' ?
                    'lab_service' : ($params['type'] === 'iv_drip' ?
                        'iv_drip_service' : ($params['type'] === 'nursing_care' ?
                            'nursing_care' : $params['type'])));

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
            if (isset($params['icon'])) {
                Media::where('fileable_id', $service->id)->where('fileable_type', 'icon')->delete();
                $path = $this->uploadFile($params['icon']);
                $file = $params['icon'];
                $type = 'icon';
                $path = $this->storeFile($path, $file, $service->id, $type);
            }

            DB::commit();
            return $service;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function uploadFile($file)
    {
        return Storage::putFile('public/media', $file);
    }

    protected function storeFile($path, $file, $data, $type)
    {
        return Media::create([
            'path' => basename($path),
            'field_name' => 'images',
            'name' => $file->getClientOriginalName(),
            'fileable_type' => $type,
            'fileable_id' => $data,
        ]);
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
            // if (!empty($params['services']) && is_array($params['services'])) {
            //     $bundleServices = collect($params['services'])->map(fn($serviceId) => [
            //         'bundle_id' => $bundle->id,
            //         'service_id' => $serviceId,
            //         'created_at' => now(),
            //     ])->toArray();

            // BundleService::insert($bundleServices);
            // }

            if (isset($params['icon'])) {
                $path = $this->uploadFile($params['icon']);
                $file = $params['icon'];
                $type = 'icon';
                $path = $this->storeFile($path, $file, $bundle->id, $type);
            }

            DB::commit();

            // Return bundle with related services
            return $bundle;
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
                'about' => $params['about'] ?? $bundle->about,
                'parameters_included' => $params['parameters_included'] ?? $bundle->parameters_included,
                'precautions' => $params['precautions'] ?? $bundle->precautions,
                'fasting_requirments' => $params['fasting_requirments'] ?? $bundle->fasting_requirments,
                'turnaround_time' => $params['turnaround_time'] ?? $bundle->turnaround_time,
                'when_to_get_tested' => $params['when_to_get_tested'] ?? $bundle->when_to_get_tested,
                'price'       => $params['price'] ?? $bundle->price,
                'status'      => $params['status'] ?? $bundle->status,
                'file'        => $params['file'] ?? $bundle->file,
            ]);

            // Sync services if provided
            // if (!empty($params['services']) && is_array($params['services'])) {
            //     $bundle->services()->sync($params['services']);
            // }

            if (isset($params['icon'])) {
                Media::where('fileable_id', $bundle->id)->where('fileable_type', 'icon')->delete();
                $path = $this->uploadFile($params['icon']);
                $file = $params['icon'];
                $type = 'icon';
                $path = $this->storeFile($path, $file, $bundle->id, $type);
            }

            DB::commit();

            // Return the updated bundle with related services
            return $bundle;
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
