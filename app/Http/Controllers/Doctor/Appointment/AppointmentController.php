<?php

namespace App\Http\Controllers\Doctor\Appointment;

use App\Models\Chat;
use Firebase\JWT\JWT;
use App\Models\Message;
use App\Models\WebexToken;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Filters\Doctor\AppointmentFilters;
use App\Http\Requests\Appointment\RescheduleRequest;
use App\Repositories\Appointment\AppointmentRepository;
use App\Http\Requests\Appointment\GetAppointmentRequest;
use App\Http\Requests\Appointment\BookAppointmentRequest;
use App\Http\Requests\Payment\CreateAppointmentPaymentRequest;

class AppointmentController extends Controller
{
    private AppointmentRepository $appointment;
    protected $webexService;


    public function __construct(AppointmentRepository $appointmentRepo)
    {
        $this->appointment = $appointmentRepo;
        $this->appointment->setModel(Appointment::make());
    }

    public function index(GetAppointmentRequest $request, AppointmentFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
                'owner' => 1,
                'payment_status' => 'paid'

            ]);
            $appointment = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['bookable', 'user', 'familyMember']

                );
            $data = api_successWithData('appointment listing', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function create(BookAppointmentRequest $request)
    {
        try {
            $data = $this->appointment->create($request->validated());
            $data = api_successWithData('booking successfull', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update(RescheduleRequest $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $data = $this->appointment->reschedule($id, $validatedData);
            $data = api_successWithData('Successfully updated.', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function purchase(CreateAppointmentPaymentRequest $request): JsonResponse
    {
        try {
            $paymentResponse = $this->appointment->payment($request->validated());

            return response()->json(api_successWithData('Payment confirmed successfully', $paymentResponse), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function show($id)
    {
        try {

            $appointment = $this->appointment
                ->findById(
                    $id,
                    relations: [
                        'user',
                        'familyMember',
                        'address',
                        'reviews',
                        'payment',
                        'prescription',
                        'bookable.webexToken'
                    ]
                );
            $data = api_successWithData('appointment details', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function cancel($id)
    {
        try {

            $appointment = $this->appointment->findById($id);
            if ($appointment) {
                $appointment = $this->appointment
                    ->update($id, [
                        'status' => 'cancelled',
                        'provider_reason' => request('reason')
                    ]);
                $appointment =  api_success('Booking cancelled successfully');
            } else {
                $appointment = api_error('Booking Not Found');
            }

            return response()->json($appointment, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function notes($id)
    {
        try {

            $appointment = $this->appointment->findById($id);
            if ($appointment) {
                $appointment = $this->appointment
                    ->update($id, [
                        'notes' => request('notes')

                    ]);
                $appointment =  api_success('Note submitted successfully');
            } else {
                $appointment = api_error('Booking Not Found');
            }

            return response()->json($appointment, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMonthlyAppointments()
    {
        try {
            $appointments = $this->appointment->getDoctorAppointmentsGroupedByMonth();
            return response()->json(api_successWithData('Appointments fetched successfully.', $appointments));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateAppointmentStatus(Request $request, $id, $action)
    {
        try {
            $appointment = $this->appointment->findById(
                $id,
                relations: [
                    'bookable.file',
                    'familyMember',
                    'serviceProvider.file',
                    'serviceProvider',
                    'address',
                    'bundleServices.service.file',
                    'reviews',
                    'user'
                ]
            );
            $user = $request->user();

            if (!$appointment) {
                return response()->json(api_error('Booking Not Found'), Response::HTTP_OK);
            }

            $statusUpdate = [];
            $message = '';

            $doctor = WebexToken::where('doctor_id', $user->id)->first();
            $accessToken = $doctor?->access_token;
            if (!$doctor || !$accessToken) {
                return response()->json(api_error('Doctor Webex credentials not found'), Response::HTTP_BAD_REQUEST);
            }
            // Get the actual email tied to this access token
            $hostEmail = 'h.fouad@wellxa.ae'; // This user is Owner of the project who has webex license

            switch ($action) {
                case 'cancel':
                    $request->validate([
                        'reason' => 'required|string|max:255',
                    ]);

                    $statusUpdate = [
                        'status' => 'cancelled',
                        'provider_reason' => $request->input('reason'),
                    ];
                    $message = 'Booking cancelled successfully';
                    $this->appointment->sendCancelNotification($appointment);
                    break;

                case 'start-session':
                case 'start-call':

                    //Webex meeting settings
                    $meetingData = [
                        'title' => 'Medical Consultation - Appointment #' . $id,
                        'start' => now()->toIso8601String(),
                        'end' => now()->addMinutes(60)->toIso8601String(),
                        'timezone' => 'Asia/Dubai',
                        'enabledJoinBeforeHost' => false,
                        'allowFirstUserToBeCoHost' => true,
                        'joinBeforeHostMinutes' => 0,
                        'hostEmail' => $hostEmail,
                        'excludePassword' => true,
                        "allowMediaInLobby" => true,
                        "meetingOptions" => [
                            "enabledChat" => true,
                            "enabledVideo" => true,
                            "enabledNote" => true,
                            "noteType" => "allowAll",
                            "enabledAutoShareVideo" => true, // Add this to ensure host video is shared
                        ],
                        "hostVideoOn" => true, // Explicitly enable host video
                        "participantVideoOn" => true, // Explicitly enable participant video
                    ];

                    $response = $this->makeWebexApiCall($accessToken, $meetingData);

                    if ($response['success']) {
                        $meeting = $response['data'];

                        $statusUpdate = [
                            'appointment_status' => 'inprogress',
                            'is_live' => 1,
                            'meetingId' => $meeting['id'],
                            'sipAddress' => $meeting['sipAddress'],
                            'webLink' => $meeting['webLink'],
                            'host_join_url' => $meeting['webLink'],
                            'guest_join_url' => $meeting['webLink'],
                            'password' => $meeting['password'] ?? null,
                            'host_key' => $meeting['hostKey'] ?? null,
                            'meeting_number' => $meeting['meetingNumber'] ?? null,
                        ];

                        $notificationPayload = [
                            'appointment_id' => $id,
                            'appointment_status' => 'inprogress',
                            'is_live' => 1,
                            'meetingId' => $meeting['id'],
                            'sipAddress' => $meeting['sipAddress'],
                            'webLink' => $meeting['webLink'],
                            'password' => $meeting['password'] ?? null,
                            'host_key' => $meeting['hostKey'] ?? null,
                            'meeting_number' => $meeting['meetingNumber'] ?? null,
                        ];

                        $message = $action === 'start-session'
                            ? 'Video Session started successfully'
                            : 'Call Session started successfully';
                        $this->appointment->sendStartNotification($appointment, $notificationPayload, $action);
                    } else {
                        return response()->json(api_error('Failed to create meeting'), Response::HTTP_BAD_REQUEST);
                    }
                    break;

                case 'start-chat':
                    $statusUpdate = [
                        'appointment_status' => 'inprogress',
                        'is_live' => 1
                    ];
                    $message = 'Chat started successfully';

                    $chat = Chat::where([
                        ['sender_id', $user->id],
                        ['receiver_id', $appointment->user_id],
                        ['appointment_id', $appointment->id],
                    ])->first();

                    if (!$chat) {
                        $chat = Chat::create([
                            'sender_id' => $user->id,
                            'receiver_id' => $appointment->user_id,
                            'chat_type' => 'user_doctor',
                            'appointment_id' => $appointment->id,
                            'type' => 'session',
                        ]);

                        Message::create([
                            'chat_id' => $chat->id,
                            'sender_id' => $user->id,
                            'receiver_id' => $appointment->user_id,
                            'message' => "Hello, Doctor here, how can I help you?",
                            'is_read' => false,
                        ]);
                    }

                    $notificationPayload = [
                        'appointment_id' => $id,
                        'appointment_status' => 'inprogress',
                        'is_live' => 1,
                    ];

                    $this->appointment->sendStartNotification($appointment, $notificationPayload, 'start-chat');
                    break;

                case 'end':
                    $statusUpdate = [
                        'appointment_status' => 'completed',
                        'is_live' => 0
                    ];
                    $message = 'Session ended successfully';
                    $this->appointment->sendCompletedNotification($appointment);
                    break;

                default:
                    return response()->json(api_error('Invalid action'), Response::HTTP_BAD_REQUEST);
            }

            $this->appointment->update($id, $statusUpdate);
            $data = Appointment::with('chats')->find($id);

            return response()->json(api_successWithData($message, $data), Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(api_error($e->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //for create meeting
    private function makeWebexApiCall($accessToken, $meetingData, $maxRetries = 3)
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {

                $response = Http::timeout(45) // Increased timeout
                    ->connectTimeout(10) // Connection timeout
                    ->retry(2, 1000) // Retry 2 times with 1 second delay
                    ->withToken($accessToken)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post('https://webexapis.com/v1/meetings', $meetingData);

                \Log::info('Webex API response received', [
                    'status' => $response->status(),
                    'attempt' => $attempt,
                    'response_size' => strlen($response->body())
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    // Log the host email
                    \Log::info('Webex Meeting Created', [
                        'hostEmail' => $data['hostEmail'] ?? 'N/A', // fallback if not present
                        'meetingId' => $data['id'] ?? null,
                        'title' => $data['title'] ?? null
                    ]);
                    return [
                        'success' => true,
                        'data' => $response->json()
                    ];
                } else {
                    $errorData = $response->json();
                    \Log::warning('Webex API error response', [
                        'status' => $response->status(),
                        'error' => $errorData,
                        'attempt' => $attempt
                    ]);

                    // Don't retry for client errors (4xx)
                    if ($response->status() >= 400 && $response->status() < 500) {
                        return [
                            'success' => false,
                            'error' => $errorData['message'] ?? 'API request failed with status ' . $response->status()
                        ];
                    }

                    // Retry for server errors (5xx) or network issues
                    if ($attempt >= $maxRetries) {
                        return [
                            'success' => false,
                            'error' => 'Server error after ' . $maxRetries . ' attempts: ' . ($errorData['message'] ?? 'Unknown error')
                        ];
                    }

                    sleep(2 * $attempt); // Exponential backoff
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                \Log::error("Webex API connection error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);

                if ($attempt >= $maxRetries) {
                    return [
                        'success' => false,
                        'error' => 'Connection timeout. Please check your internet connection and try again.'
                    ];
                }

                sleep(3 * $attempt); // Longer wait for connection issues

            } catch (\Exception $e) {
                \Log::error("Webex API unexpected error on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'type' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                    'response' => method_exists($e, 'response') && $e->response
                        ? $e->response->body()
                        : 'No response body'
                ]);
                if ($attempt >= $maxRetries) {
                    return [
                        'success' => false,
                        'error' => 'Network error: ' . $e->getMessage()
                    ];
                }

                sleep(2 * $attempt);
            }
        }

        return [
            'success' => false,
            'error' => 'Maximum retry attempts reached'
        ];
    }

    //refresh host token
    public static function refreshHostToken()
    {
        $token = WebexToken::whereNull('doctor_id')->first();
        if (!$token) {
            throw new \Exception('Host token not found');
        }

        $response = Http::asForm()->post('https://webexapis.com/v1/access_token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.webex.client_id'),
            'client_secret' => config('services.webex.client_secret'),
            'refresh_token' => $token->refresh_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            $token->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);

            return $token->access_token;
        } else {
            throw new \Exception('Failed to refresh host token: ' . $response->body());
        }
    }


    //webex callback
    public function webexCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');

        // Extract doctor ID from state
        if (str_contains($state, '__doctor_')) {
            $parts = explode('__doctor_', $state);
            $doctorId = $parts[1] ?? null;
        } else {
            return response()->json(api_error('Doctor ID not found in state.'), 400);
        }

        if (!$doctorId) {
            return response()->json(api_error('Missing doctor ID'), 400);
        }

        $response = Http::timeout(60)->asForm()->post('https://webexapis.com/v1/access_token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.webex.client_id'),
            'client_secret' => config('services.webex.client_secret'),
            'redirect_uri' => config('services.webex.redirect_uri'),
            'code' => $code,
        ]);

        $data = $response->json();

        \Log::info('Webex Callback api', [
            'status' => $response->status(),
            'response' => strlen($response->body())
        ]);


        if ($response->successful() && isset($data['access_token'])) {
            WebexToken::updateOrCreate(
                ['doctor_id' => $doctorId],
                [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => now()->addSeconds($data['expires_in']),
                ]
            );

            return redirect()->away(config('services.webex.frontend_uri'));
        } else {
            \Log::error('Webex token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(), // This gives full error message
                'json' => $data,             // If JSON decodable
            ]);
            return response()->json(api_error('Failed to retrieve access token from Webex.'), 400);
        }
    }

    //generate guest token
    public function generateGuestToken(Request $request)
    {

        $request->validate([
            'name' => 'required|string',
            'user_id' => 'required|string', // used as 'sub'
        ]);

        $issuer = config('services.webex.guest_issuer_id');
        $secret = config('services.webex.guest_secret');

        $payload = [
            'sub' => $request->user_id,             // unique user identifier
            'name' => $request->name,               // display name
            'iss'  => $issuer,                      // Webex Guest Issuer ID
            'exp'  => Carbon::now()->addMinutes(60)->timestamp, // expiration in UNIX
        ];

        $jwt = JWT::encode($payload, $secret, 'HS256');

        return response()->json([
            'token' => $jwt,
            'expires_at' => $payload['exp']
        ]);
    }
}
