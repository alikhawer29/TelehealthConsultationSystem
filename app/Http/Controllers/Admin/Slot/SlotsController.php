<?php

namespace App\Http\Controllers\Admin\Slot;

use App\Models\Slot;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Filters\Admin\SlotsFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Slot\SlotRequest;
use App\Http\Requests\Slot\UpdateSlotRequest;
use App\Models\Service;
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

            $slotType = request('slot_type');
            $filters->extendRequest([
                'order' => 1,
                'groupByDate' => 1
            ]);

            $data = $this->slot
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                    relations: ['referenceable']
                );
            $data = api_successWithData('slots data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function history(Request $request, SlotsFilters $filters)
    {
        try {

            $filters->extendRequest([
                'order' => 1,
                'groupByDate' => 1,
                'slot_type' => ['homecare', 'nursing_care'], // array of types
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

    public function create(SlotRequest $request)
    {
        try {
            $data = $this->slot->create($request->validated());
            $data = api_successWithData('slot created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update($id, UpdateSlotRequest $request)
    {
        try {
            $data = $this->slot->updateSlots($id, $request->validated());
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

            // Get slots for the same service
            $slots = Slot::where('reference_id', $slot->reference_id)->where('slot_type', $slot->slot_type)
                ->where('created_at', $slot->created_at)
                ->with('referenceable')
                ->get();

            // Get all booked slot IDs
            $bookedSlots = Appointment::where('service_type', '!=', 'doctor')
                ->where('appointment_status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->pluck('slot_id')
                ->toArray();

            // Append isBooked key
            $slots = $slots->map(function ($slot) use ($bookedSlots) {
                $slot->isBooked = in_array($slot->id, $bookedSlots);
                $slot->service_name = $slot->reference ? $slot->reference->name : null;

                return $slot;
            });

            return response()->json(api_successWithData('Slots details', $slots), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function services()
    {
        try {
            $serviceType = request('service_type') === 'homecare' ? 'homecare_service' : 'nursing_care';
            $furtherType = request('service_type') === 'homecare' ? 'homecare' : 'nursing_care';
            // Get all reference_ids from Slot where reference_type is Service and slot_type is homecare_Service
            $createdServices = Slot::where('reference_type', 'App\Models\Service')
                ->where('slot_type', $serviceType)
                ->where('modification_status', 'active')
                ->pluck('reference_id'); // Use pluck to get an array of reference_ids

            // Get the services that are 'homecare' and are active (status = 1)
            $services = Service::where('type', $furtherType)
                ->where('status', 1)
                ->whereNotIn('id', $createdServices) // Exclude services that are already in createdServices
                ->select('id', 'name')
                ->get();
            return response()->json(api_successWithData('services', $services), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function destroy($id)
    {
        try {
            $this->slot->deleteSlot($id);
            $data = api_success('Slot deleted');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            // This will now catch the new descriptive exception
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST); // Or use HTTP_CONFLICT (409)
        }
    }

    public function checkSlotsAvailablity()
    {
        try {
            $type = request()->input('type');
            $data = $this->slot->checkSlotsAvailablity($type);
            return response()->json(api_successWithData('Slot Availability', $data), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_BAD_REQUEST);
        }
    }
}
