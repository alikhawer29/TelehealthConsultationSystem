<?php

namespace App\Repositories\Slot;

use Carbon\Carbon;
use App\Models\Day;
use App\Models\Bank;
use App\Models\Slot;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Bank\BankRepository;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Models\Appointment;
use App\Models\Service;
use App\Repositories\Slot\ShopRepositoryContract;

class SlotRepository extends BaseRepository implements SlotRepositoryContract
{

    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    //for admin - create
    public function create(array $params)
    {
        DB::beginTransaction();
        try {
            $type = $params['type'];
            $createdSlots = [];

            // Define days mapping to numbers (Monday = 1, ..., Sunday = 7)
            $daysMapping = [
                'monday'    => 1,
                'tuesday'   => 2,
                'wednesday' => 3,
                'thursday'  => 4,
                'friday'    => 5,
                'saturday'  => 6,
                'sunday'    => 7
            ];

            if (empty($params['slots'])) {
                throw new \Exception("Slots data is required.");
            }

            $slotType = match ($type) {
                'homecare' => 'homecare_service',
                'lab' => 'lab_service',
                'iv_drip' => 'iv_drip',
                'nursing_care' => 'nursing_care',
                default => '',
            };
            $referenceType = 'App\\Models\\Service';
            $serviceId = $params['service'] ?? null;

            // ✅ Step 1: Mark previous slots as 'updated'
            Slot::where('reference_type', $referenceType)
                ->where('reference_id', $serviceId)
                ->where('slot_type', $slotType)
                ->where('modification_status', 'active')
                ->update(['modification_status' => 'updated']);

            foreach ($params['slots'] as $day => $slotData) {
                $dayNumber = $daysMapping[strtolower($day)] ?? null;

                if (!$dayNumber || empty($slotData['times'])) {
                    continue; // Skip if invalid day or empty times
                }

                $status = $slotData['status'] ?? 0;

                // Sort slots by start_time for proper indexing
                usort($slotData['times'], fn($a, $b) => strtotime($a['start_time']) - strtotime($b['start_time']));

                foreach ($slotData['times'] as $index => $time) {
                    if ($this->slotExists($slotType, $referenceType, $serviceId, $day, $time)) {
                        throw new \Exception("Slot for $day from {$time['start_time']} to {$time['end_time']} already exists.");
                    }

                    $createdSlots[] = Slot::create([
                        'slot_type'      => $slotType,
                        'reference_type' => $type === 'homecare' || $type === 'nursing_care' ? $referenceType : '',
                        'reference_id'   => $type === 'homecare' || $type === 'nursing_care' ? $serviceId : '',
                        'index'          => $index,
                        'day'            => $dayNumber,
                        'day_name'       => $day,
                        'start_time'     => $time['start_time'],
                        'end_time'       => $time['end_time'],
                        'status'         => $status,
                        'modification_status' => 'active', // ✅ new ones marked active

                    ]);
                }
            }

            DB::commit();
            return $createdSlots;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    //for doctor - create
    public function createDoctorSlot(array $params)
    {
        DB::beginTransaction();

        try {
            $createdSlots = [];
            $daysMapping = [
                'monday'    => 1,
                'tuesday'   => 2,
                'wednesday' => 3,
                'thursday'  => 4,
                'friday'    => 5,
                'saturday'  => 6,
                'sunday'    => 7
            ];

            if (empty($params['slots'])) {
                throw new \Exception("Slots data is required.");
            }

            $slotType      = 'doctor';
            $referenceType = 'App\\Models\\User';
            $doctorId      = request()->user()->id;

            // ✅ Step 1: Mark previous slots as 'updated'
            Slot::where('reference_type', $referenceType)
                ->where('reference_id', $doctorId)
                ->where('slot_type', $slotType)
                ->where('modification_status', 'active')
                ->update(['modification_status' => 'updated']);

            // ✅ Step 2: Create new slots with 'active' status
            foreach ($params['slots'] as $day => $slotData) {
                $dayNumber = $daysMapping[strtolower($day)] ?? null;
                if (!$dayNumber || empty($slotData['times'])) continue;

                $status = $slotData['status'] ?? 0;

                usort($slotData['times'], fn($a, $b) => strtotime($a['start_time']) - strtotime($b['start_time']));

                foreach ($slotData['times'] as $index => $time) {
                    $createdSlots[] = Slot::create([
                        'slot_type'          => $slotType,
                        'reference_type'     => $referenceType,
                        'reference_id'       => $doctorId,
                        'index'              => $index,
                        'day'                => $dayNumber,
                        'day_name'           => $day,
                        'start_time'         => $time['start_time'],
                        'end_time'           => $time['end_time'],
                        'status'             => $status,
                        'modification_status' => 'active', // ✅ new ones marked active
                    ]);
                }
            }

            DB::commit();
            return $createdSlots;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    //for doctor - update
    public function updateDoctorSlot($id, array $params)
    {
        DB::beginTransaction();

        try {
            $createdSlots = [];
            $daysMapping = [
                'monday'    => 1,
                'tuesday'   => 2,
                'wednesday' => 3,
                'thursday'  => 4,
                'friday'    => 5,
                'saturday'  => 6,
                'sunday'    => 7
            ];

            if (empty($params['slots'])) {
                throw new \Exception("Slots data is required.");
            }

            $slotType      = 'doctor';
            $referenceType = 'App\\Models\\User';
            $doctorId      = request()->user()->id;

            // 1. Build incoming slot keys
            $requestedSlotKeys = [];
            foreach ($params['slots'] as $day => $slotData) {
                if (!empty($slotData['times'])) {
                    foreach ($slotData['times'] as $time) {
                        $requestedSlotKeys[] = strtolower($day) . '|' . $time['start_time'] . '|' . $time['end_time'];
                    }
                }
            }
            $bookedSlots = collect();

            //get doctor booked slots
            $bookedSlots = Slot::where('reference_type', $referenceType)
                ->where('reference_id', $doctorId)
                ->where('slot_type', $slotType)
                ->where('modification_status', 'active')
                ->whereHas('appointments')
                ->get()
                ->keyBy(function ($slot) {
                    return strtolower($slot->day_name) . '|' .
                        \Carbon\Carbon::parse($slot->start_time)->format('H:i') . '|' .
                        \Carbon\Carbon::parse($slot->end_time)->format('H:i');
                });

            // 3. Mark old slots as updated except the booked slots that are still present in request
            $allOldSlots = Slot::where('reference_type', $referenceType)
                ->where('reference_id', $doctorId)
                ->where('slot_type', $slotType)
                ->where('modification_status', 'active')
                ->get();

            foreach ($allOldSlots as $slot) {
                $key = strtolower($slot->day_name) . '|' .
                    \Carbon\Carbon::parse($slot->start_time)->format('H:i') . '|' .
                    \Carbon\Carbon::parse($slot->end_time)->format('H:i');

                $slot->modification_status = 'updated';
                $slot->save();
            }

            // ✅ Step 2: Create new slots with 'active' status
            foreach ($params['slots'] as $day => $slotData) {
                $dayNumber = $daysMapping[strtolower($day)] ?? null;
                if (!$dayNumber || empty($slotData['times'])) continue;

                $status = $slotData['status'] ?? 0;

                usort($slotData['times'], fn($a, $b) => strtotime($a['start_time']) - strtotime($b['start_time']));

                foreach ($slotData['times'] as $index => $time) {

                    $key = strtolower($day) . '|' . $time['start_time'] . '|' . $time['end_time'];

                    $newSlot = Slot::create([
                        'slot_type'          => $slotType,
                        'reference_type'     => $referenceType,
                        'reference_id'       => $doctorId,
                        'index'              => $index,
                        'day'                => $dayNumber,
                        'day_name'           => $day,
                        'start_time'         => $time['start_time'],
                        'end_time'           => $time['end_time'],
                        'status'             => $status,
                        'modification_status' => 'active', // ✅ new ones marked active
                    ]);

                    // Update appointment table slot id with latest slot id
                    if ($bookedSlots->has($key)) {
                        Appointment::where('slot_id', $bookedSlots[$key]->id)->update(['slot_id' => $newSlot->id]);
                    }

                    $createdSlots[] = $newSlot;
                }
            }

            DB::commit();
            return $createdSlots;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function updateSlots($id, array $params)
    {
        DB::beginTransaction();
        try {
            if (empty($params['slots'])) {
                throw new \Exception("Slots data is required.");
            }

            $type = $params['type'];
            $slotType = match ($type) {
                'homecare' => 'homecare_service',
                'lab' => 'lab_service',
                'iv_drip' => 'iv_drip',
                'nursing_care' => 'nursing_care',
                default => '',
            };
            $referenceType = 'App\\Models\\Service';
            $serviceId = $params['service'] ?? null;

            // Define days mapping to numbers
            $daysMapping = [
                'monday'    => 1,
                'tuesday'   => 2,
                'wednesday' => 3,
                'thursday'  => 4,
                'friday'    => 5,
                'saturday'  => 6,
                'sunday'    => 7
            ];

            $createdSlots = [];

            // 1. Build incoming slot keys
            $requestedSlotKeys = [];
            foreach ($params['slots'] as $day => $slotData) {
                if (!empty($slotData['times'])) {
                    foreach ($slotData['times'] as $time) {
                        $requestedSlotKeys[] = strtolower($day) . '|' . $time['start_time'] . '|' . $time['end_time'];
                    }
                }
            }

            // 2. Fetch booked slots (only for homecare)
            $bookedSlots = collect();
            if ($slotType === 'homecare_service' || $slotType === 'nursing_care') {

                //get homecare service booked slots
                $bookedSlots = Slot::where('reference_type', $referenceType)
                    ->where('reference_id', $serviceId)
                    ->where('slot_type', $slotType)
                    ->where('modification_status', 'active')
                    ->whereHas('appointments')
                    ->get()
                    ->keyBy(function ($slot) {
                        return strtolower($slot->day_name) . '|' .
                            \Carbon\Carbon::parse($slot->start_time)->format('H:i') . '|' .
                            \Carbon\Carbon::parse($slot->end_time)->format('H:i');
                    });

                // 3. Mark old slots as updated except the booked slots that are still present in request
                $allOldSlots = Slot::where('reference_type', $referenceType)
                    ->where('reference_id', $serviceId)
                    ->where('slot_type', $slotType)
                    ->where('modification_status', 'active')
                    ->get();

                foreach ($allOldSlots as $slot) {
                    $key = strtolower($slot->day_name) . '|' .
                        \Carbon\Carbon::parse($slot->start_time)->format('H:i') . '|' .
                        \Carbon\Carbon::parse($slot->end_time)->format('H:i');

                    $slot->modification_status = 'updated';
                    $slot->save();
                }
            } else {

                //get lab service booked slots
                $bookedSlots = Slot::where('reference_type', '')
                    ->where('reference_id', 0)
                    ->where('slot_type', $slotType)
                    ->where('modification_status', 'active')
                    ->whereHas('appointments')
                    ->get()
                    ->keyBy(function ($slot) {
                        return strtolower($slot->day_name) . '|' .
                            \Carbon\Carbon::parse($slot->start_time)->format('H:i') . '|' .
                            \Carbon\Carbon::parse($slot->end_time)->format('H:i');
                    });

                // 3. Mark old slots as updated except the booked slots that are still present in request
                $allOldSlots = Slot::where('reference_type', '')
                    ->where('reference_id', 0)
                    ->where('slot_type', $slotType)
                    ->where('modification_status', 'active')
                    ->get();

                foreach ($allOldSlots as $slot) {
                    $key = strtolower($slot->day_name) . '|' .
                        \Carbon\Carbon::parse($slot->start_time)->format('H:i') . '|' .
                        \Carbon\Carbon::parse($slot->end_time)->format('H:i');

                    $slot->modification_status = 'updated';
                    $slot->save();
                }
            }

            // 4. Create only non-booked or new slots
            foreach ($params['slots'] as $day => $slotData) {
                $dayNumber = $daysMapping[strtolower($day)] ?? null;
                if (!$dayNumber || empty($slotData['times'])) {
                    continue;
                }

                $status = $slotData['status'] ?? 0;

                usort($slotData['times'], fn($a, $b) => strtotime($a['start_time']) - strtotime($b['start_time']));

                foreach ($slotData['times'] as $index => $time) {
                    $key = strtolower($day) . '|' . $time['start_time'] . '|' . $time['end_time'];

                    $newSlot = Slot::create([
                        'slot_type'      => $slotType,
                        'reference_type' => $type === 'homecare' || $type === 'nursing_care' ? $referenceType : '',
                        'reference_id'   => $type === 'homecare' || $type === 'nursing_care' ? $serviceId : '',
                        'index'          => $index,
                        'day'            => $dayNumber,
                        'day_name'       => $day,
                        'start_time'     => $time['start_time'],
                        'end_time'       => $time['end_time'],
                        'status'         => $status,
                        'modification_status' => 'active',
                        // 'parent_id' => $slot->id
                    ]);

                    // Update appointment table slot id with latest slot id
                    if ($bookedSlots->has($key)) {
                        Appointment::where('slot_id', $slot->id)->update(['slot_id' => $newSlot->id]);
                    }

                    $createdSlots[] = $newSlot;
                }
            }


            DB::commit();
            return $createdSlots;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }



    /**
     * Check if a slot already exists for given parameters.
     */
    private function slotExists($slotType, $referenceType, $serviceId, $day, $time)
    {
        return Slot::where([
            ['slot_type', '=', $slotType],
            ['day_name', '=', $day],
            ['start_time', '=', $time['start_time']],
            ['end_time', '=', $time['end_time']],
        ])
            ->when($serviceId, fn($query) => $query->where('reference_id', $serviceId)
                ->where('reference_type', $referenceType))
            ->exists();
    }




    //for admin


    function deleteUnbookedSlots($final)
    {
        try {
            $this->model
                ->whereIn('id', $final)
                // ->where('booking_status', '!=', 1)
                ->delete();

            return 'successfully created';
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function slots($params)
    {
        try {
            // Get the authenticated user
            $user = request()->user();
            $id = $user->id;
            $owner_class = get_class($user);
            $weekDay = Carbon::parse($params)->dayOfWeek;

            if ($params) {
                $get = $this->model->where('slotable_type', $owner_class)
                    ->where('slotable_id', $id)
                    ->when($params, function ($q) use ($weekDay) {
                        $q->where('day', $weekDay);
                    })
                    ->orderBy('start_time') // Sort by start_time in the query
                    ->get(['id', 'index', 'start_time', 'end_time', 'booking_status', 'day', 'name']);
            } else {
                $get = Day::with([
                    'slots:id,index,start_time,end_time,booking_status,day,status',
                    'slots' => function ($q) use ($params, $owner_class, $id) {
                        $q->where('slotable_id', $id);
                        $q->where('slotable_type', $owner_class);
                        $q->orderBy('start_time'); // Sort by start_time in the query
                    }
                ])
                    ->orderBy('day')
                    ->get();
            }

            // Sort slots manually if necessary
            foreach ($get as $day) {
                if (isset($day->slots) && is_array($day->slots)) {
                    $day->slots = collect($day->slots)->sortBy('start_time')->values()->all();
                }
            }

            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteSlot($id)
    {
        $slot = $this->model->findOrFail($id);

        // 2. Check if this specific slot has any 'scheduled' or 'requested' appointments
        $isBooked = Appointment::where('slot_id', $id)
            ->whereIn('status', ['scheduled', 'requested'])
            ->exists(); // exists() is efficient, returns true/false

        if ($isBooked) {
            // Throw a specific exception if the slot is booked
            throw new \Exception('Cannot delete slot. It has associated appointments in a scheduled or requested state.');
        }

        $this->model
            ->where('slot_type', $slot->slot_type)
            ->where('modification_status', 'active')
            ->delete(); // Or $this->model->where('id', $id)->delete();

        return 'Successfully deleted';
    }

    public function checkSlotsAvailablity($type)
    {
        // Get all active slot assignments for this type

        $serviceType = match ($type) {
            'homecare' => 'homecare_service',
            'lab' => 'lab_service',
            'iv_drip' => 'iv_drip',
            'nursing_care' => 'nursing_care',
            default => '',
        };


        $assignedSlots = $this->model
            ->where('slot_type', $serviceType)
            ->where('modification_status', 'active')
            ->count('id'); // service IDs already assigned

        // If all services are assigned → false, else true
        return $assignedSlots > 0;
    }
}
