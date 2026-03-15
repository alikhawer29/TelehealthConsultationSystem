<?php

namespace App\Http\Controllers\Doctor\Sessions;

use App\Models\Appointment;
use App\Http\Controllers\Controller;
use App\Filters\Doctor\SessionFilters;
use App\Repositories\Appointment\AppointmentRepository;


class SessionsController extends Controller
{
    private AppointmentRepository $appointment;

    public function __construct(AppointmentRepository $appointmentRepo)
    {
        $this->appointment = $appointmentRepo;
        $this->appointment->setModel(Appointment::make());
    }

    public function index(SessionFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
                'owner' => 1,
                'appointment_status' => 'completed'
            ]);
            $appointments = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['user', 'chats.messages']

                );

            // 🧠 Add duration info to each appointment
            $appointments->getCollection()->transform(function ($appointment) {
                foreach ($appointment->chat as $chat) {
                    if ($chat->messages->count()) {
                        $first = $chat->messages->sortBy('created_at')->first();
                        $last = $chat->messages->sortByDesc('created_at')->first();

                        $start = \Carbon\Carbon::parse($first->created_at);
                        $end = \Carbon\Carbon::parse($last->created_at);
                        $seconds = $start->diffInSeconds($end);
                    } else {
                        $chat->duration = '00:00:00';
                    }

                    // Inject into chat object or appointment directly
                    $appointment->duration = gmdate('H:i:s', $seconds);
                }

                return $appointment;
            });

            $data = api_successWithData('session listing', $appointments);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function show($id, SessionFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
                'owner' => 1,
                'appointment_status' => 'completed'
            ]);

            $session = $this->appointment
                ->findById(
                    $id,
                    filter: $filter,
                    relations: [
                        'user',
                        'chats.messages',
                        'chats.messages.receiver',
                        'chats.messages.sender',
                        'chats.messages.receiver.file',
                        'chats.messages.sender.file'
                    ]

                );
            $data = api_successWithData('session details', $session);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }
}
