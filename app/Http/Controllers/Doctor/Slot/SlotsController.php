<?php

namespace App\Http\Controllers\Doctor\Slot;

use App\Models\Slot;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Filters\Doctor\SlotsFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Slot\DoctorSlotRequest;
use App\Http\Requests\Slot\UpdateSlotRequest;
use App\Repositories\Slot\SlotRepository;

class SlotsController extends Controller
{
    private SlotRepository $slot;

    public function __construct(SlotRepository $slotRepo, Slot $slot)
    {
        $this->slot = $slotRepo;
        $this->slot->setModel($slot);
    }

    public function index(Request $request, SlotsFilters $filters)
    {
        try {
            $filters->extendRequest([
                'order' => 1,
                'groupBy' => 1,
            ]);
            $data = $this->slot
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                );
            $data = api_successWithData('slots data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(DoctorSlotRequest $request)
    {
        try {
            $data = $this->slot->createDoctorSlot($request->validated());
            $data = api_successWithData('slot created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update($id, DoctorSlotRequest $request)
    {
        try {
            $data = $this->slot->updateDoctorSlot($id, $request->validated());
            $data = api_successWithData('slot updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            // Get slot by ID
            $slot = $this->slot->findById($id);

            if (!$slot) {
                return response()->json(api_error('Slot not found'), Response::HTTP_NOT_FOUND);
            }

            // Get slots for the same doctor
            $slots = Slot::where('reference_id', $slot->reference_id)
                ->where('slot_type', 'doctor')
                ->where('created_at', $slot->created_at)
                ->get();

            // Get all booked slot IDs
            $bookedSlots = Appointment::where('service_type', 'doctor')
                ->where('appointment_status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->pluck('slot_id')
                ->toArray();

            // Append isBooked key
            $slots = $slots->map(function ($slot) use ($bookedSlots) {
                $slot->isBooked = in_array($slot->id, $bookedSlots);
                return $slot;
            });

            return response()->json(api_successWithData('Slots details', $slots), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }
}
