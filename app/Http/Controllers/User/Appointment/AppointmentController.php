<?php

namespace App\Http\Controllers\User\Appointment;

use App\Models\Slot;
use App\Models\User;
use Firebase\JWT\JWT;
use App\Models\Appointment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Filters\User\AppointmentFilters;
use App\Http\Resources\AppointmentResource;
use App\Http\Requests\Appointment\RescheduleRequest;
use App\Repositories\Appointment\AppointmentRepository;
use App\Http\Requests\Appointment\GetAppointmentRequest;
use App\Http\Requests\Appointment\BookAppointmentRequest;
use App\Http\Requests\Payment\CreateAppointmentPaymentRequest;


class AppointmentController extends Controller
{
    private AppointmentRepository $appointment;

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
                // 'payment_status' => 'paid',
                // 'not_pending' => true
            ]);

            $appointments = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['bookable.file', 'familyMember']

                );

            // Format appointment_date for each item
            $appointments->getCollection()->transform(function ($item) {
                $item->appointment_date = \Carbon\Carbon::parse($item->appointment_date)->format('m/d/Y');
                // $item->request_date = \Carbon\Carbon::parse($item->request_date)->format('m/d/Y');
                return $item;
            });
            $data = api_successWithData('appointment listing', $appointments);
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
            $user = auth()->user();
            $validatedData = $request->validated();
            $data = $this->appointment->reschedule($id, $validatedData);
            $data = api_successWithData('Successfully updated.', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
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
            // First fetch appointment without extra relations
            $appointment = $this->appointment->findById($id);

            if (!$appointment) {
                return response()->json(api_error('Appointment not found'), Response::HTTP_NOT_FOUND);
            }

            // Decide relations based on service_type
            $relations = [
                'familyMember',
                'serviceProvider.file',
                'serviceProvider',
                'address',
                'bundleServices.service.file',
            ];

            if ($appointment->service_type === 'doctor') {
                $relations[] = 'bookable.webexToken:id,doctor_id,access_token';
            } else {
                $relations[] = 'bookable';
            }

            // Fetch again with relations
            $appointment = $this->appointment->findById($id, relations: $relations);

            $data = api_successWithData('appointment details', $appointment);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function approveReject()
    {
        try {

            $status = request('approve') == '1' ? 'approved' : 'rejected';
            $get_requested = $this->appointment->findById(request('appointment_id'));

            if ($get_requested && $get_requested->appointment_status == 'requested') {
                $appointment = $this->appointment
                    ->update(request('appointment_id'), [
                        'appointment_status' => $status,
                        'appointment_date' => request('approve') == '1' ? $get_requested->request_date : $get_requested->appointment_date,
                        'appointment_start_time' => request('approve') == '1' ? $get_requested->request_start_time : $get_requested->appointment_start_time,
                        'appointment_end_time' => request('approve') == '1' ? $get_requested->request_end_time : $get_requested->appointment_end_time,

                    ]);
                if (request('approve') == '1') {
                    $slot = Slot::where('start_time', $get_requested->request_start_time)
                        ->where('end_time', $get_requested->request_end_time)
                        ->where('slotable_type', $get_requested->bookable_type)
                        ->where('slotable_id', $get_requested->bookable_id)
                        ->first();
                    $slot->booking_status = 1;
                    $slot->save();
                    $appointment =  api_success('appointment date and time has been updated successfully');
                } else {
                    $appointment = api_success('appointment request has been rejected');
                }
            } else {
                $appointment = api_error('New slot is not requested by Coach, wait for approval');
            }

            return response()->json($appointment, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
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
                        'reason' => request('reason')

                    ]);

                $this->appointment->sendCancelNotification($appointment);
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

    public function getMonthlyAppointments()
    {
        try {
            $appointments = $this->appointment->getUserAppointmentsGroupedByMonth();
            return response()->json(api_successWithData('Appointments fetched successfully.', $appointments));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function upcomingAppointments()
    {
        try {
            $date = request('date');
            $user = request()->user();

            $appointments = Appointment::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->where(function ($query) use ($date) {
                    $query->where(function ($q) use ($date) {
                        $q->where('status', 'requested')
                            ->whereDate('request_date', $date); // exact match
                    })
                        ->orWhere(function ($q) use ($date) {
                            $q->where('status', 'scheduled')
                                ->whereDate('appointment_date', $date); // exact match
                        });
                })
                ->orderByRaw("
                CASE
                    WHEN status = 'requested' THEN request_date
                    ELSE appointment_date
                END ASC
            ")
                ->get();

            return response()->json(api_successWithData('Appointments', $appointments));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    // Service App credentials (for creating meetings)
    private $serviceAppId = 'Y2lzY29zcGFyazovL3VzL0FQUExJQ0FUSU9OL0NlNDAxOGEzZTYwMmU1OGM3NzFjMzIzMTg5MWU2ZjNhOTVlY2E2YTc2YmU1OTQ5MjFhZjRlMjQyZWJjMGYwY2Jl';
    private $serviceAppSecret = '2dd8b152b157614aa641c7183613619c73b19f9d35c2f0fe118c861bb0da6886';

    // Guest Issuer credentials (for generating guest tokens)
    private $guestIssuerId = 'Y2lzY29zcGFyazovL3VybjpURUFNOnVzLXdlc3QtMl9yL09SR0FOSVpBVElPTi9kNjM4Njc2YS0zMmI4LTRiY2ItYjg0Ni01Y2FkZGZlNmQ2YTA';
    private $sharedSecret = 'IrGC3r9JLxVMX24N7lCuxbjstPzJNdHralwCG1menMg=';

    /**
     * Base64 URL encode helper
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get Service App access token for creating meetings
     */
    private function getServiceAppToken()
    {
        try {
            $response = Http::asForm()->post('https://webexapis.com/v1/access_token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->serviceAppId,
                'client_secret' => $this->serviceAppSecret,
                'scope' => 'spark:all'
            ]);

            if (!$response->successful()) {
                Log::error('Service App token failed:', $response->json());
                return ['success' => false, 'error' => $response->body()];
            }

            $data = $response->json();
            return [
                'success' => true,
                'token' => $data['access_token'],
                'expires_in' => $data['expires_in']
            ];
        } catch (\Exception $e) {
            Log::error('Service App token exception:', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate JWT for guest users (doctors/patients)
     */
    private function generateGuestJwt($userEmail, $userName, $userType = 'guest')
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'sub' => $userEmail,
            'name' => $userName,
            'iss' => $this->guestIssuerId,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour validity
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", base64_decode($this->sharedSecret), true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Get guest access token
     */
    private function getGuestAccessToken($userEmail, $userName)
    {
        try {
            $jwt = $this->generateGuestJwt($userEmail, $userName);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwt,
                'Content-Type' => 'application/json'
            ])->post('https://webexapis.com/v1/jwt/login');

            if (!$response->successful()) {
                return ['success' => false, 'error' => $response->body()];
            }

            $data = $response->json();
            return [
                'success' => true,
                'token' => $data['token'],
                'expires_in' => $data['expiresIn'] ?? 3600
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Doctor initiates a video call with patient
     * POST /api/doctor/initiate-call
     */
    public function doctorInitiateCall(Request $request)
    {
        try {
            $request->validate([
                'doctor_id' => 'required|integer',
                'patient_id' => 'required|integer',
                'appointment_id' => 'sometimes|integer',
                'call_type' => 'sometimes|in:video,audio',
                'doctor_name' => 'required|string',
                'patient_name' => 'required|string',
                'doctor_email' => 'required|email',
                'patient_email' => 'required|email',
            ]);

            // Get Service App token for creating meeting
            $serviceToken = $this->getServiceAppToken();
            if (!$serviceToken['success']) {
                return response()->json([
                    'error' => 'Failed to get service token',
                    'details' => $serviceToken['error']
                ], 500);
            }

            // Create meeting
            $meetingTitle = "Consultation: Dr. {$request->doctor_name} & {$request->patient_name}";
            $startTime = Carbon::now()->addMinutes(1);
            $endTime = $startTime->copy()->addMinutes(60);

            $meetingPayload = [
                'title' => $meetingTitle,
                'start' => $startTime->toIso8601String(),
                'end' => $endTime->toIso8601String(),
                'enabledAutoRecordMeeting' => false,
                'allowAnyUserToBeCoHost' => true,
                'joinBeforeHostMinutes' => 0,
                'enableConnectAudioBeforeHost' => false,
                'publicMeeting' => false,
                'meetingOptions' => [
                    'enabledChat' => true,
                    'enabledVideo' => $request->input('call_type', 'video') === 'video',
                    'enabledNote' => false
                ]
            ];

            $meetingResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceToken['token'],
                'Content-Type' => 'application/json'
            ])->post('https://webexapis.com/v1/meetings', $meetingPayload);

            if (!$meetingResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to create meeting',
                    'details' => $meetingResponse->json()
                ], 500);
            }

            $meeting = $meetingResponse->json();

            // Generate guest tokens for both doctor and patient
            $doctorToken = $this->getGuestAccessToken($request->doctor_email, $request->doctor_name);
            $patientToken = $this->getGuestAccessToken($request->patient_email, $request->patient_name);

            if (!$doctorToken['success'] || !$patientToken['success']) {
                return response()->json([
                    'error' => 'Failed to generate guest tokens',
                    'doctor_token_error' => !$doctorToken['success'] ? $doctorToken['error'] : null,
                    'patient_token_error' => !$patientToken['success'] ? $patientToken['error'] : null,
                ], 500);
            }

            // Store meeting details in database (optional)
            // $appointment = Appointment::create([
            //     'doctor_id' => $request->doctor_id,
            //     'patient_id' => $request->patient_id,
            //     'meeting_id' => $meeting['id'],
            //     'meeting_link' => $meeting['webLink'],
            //     'meeting_password' => $meeting['password'] ?? null,
            //     'scheduled_at' => $startTime,
            //     'status' => 'scheduled'
            // ]);

            return response()->json([
                'success' => true,
                'meeting' => [
                    'id' => $meeting['id'],
                    'title' => $meeting['title'],
                    'webLink' => $meeting['webLink'],
                    'sipAddress' => $meeting['sipAddress'] ?? null,
                    'meetingNumber' => $meeting['meetingNumber'] ?? null,
                    'password' => $meeting['password'] ?? null,
                    'start' => $meeting['start'],
                    'end' => $meeting['end'],
                ],
                'tokens' => [
                    'doctor_token' => $doctorToken['token'],
                    'patient_token' => $patientToken['token'],
                ],
                'join_urls' => [
                    'doctor_join_url' => url("/video-call/doctor/{$meeting['id']}"),
                    'patient_join_url' => url("/video-call/patient/{$meeting['id']}"),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Doctor initiate call error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to initiate call',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Patient joins the call
     * GET /api/patient/join-call/{meetingId}
     */
    public function patientJoinCall($meetingId, Request $request)
    {
        try {
            $request->validate([
                'patient_name' => 'required|string',
                'patient_email' => 'required|email',
            ]);

            // Generate guest token for patient
            $patientToken = $this->getGuestAccessToken(
                $request->patient_email,
                $request->patient_name
            );

            if (!$patientToken['success']) {
                return response()->json([
                    'error' => 'Failed to generate patient token',
                    'details' => $patientToken['error']
                ], 500);
            }

            // Get meeting details
            $serviceToken = $this->getServiceAppToken();
            if ($serviceToken['success']) {
                $meetingResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $serviceToken['token']
                ])->get("https://webexapis.com/v1/meetings/{$meetingId}");

                $meeting = $meetingResponse->successful() ? $meetingResponse->json() : null;
            }

            return response()->json([
                'success' => true,
                'patient_token' => $patientToken['token'],
                'meeting' => $meeting,
                'join_url' => url("/video-call/patient/{$meetingId}")
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to join call',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get meeting join info for frontend
     * GET /api/meeting/{meetingId}/join-info
     */
    public function getMeetingJoinInfo($meetingId, Request $request)
    {
        try {
            $request->validate([
                'user_name' => 'required|string',
                'user_email' => 'required|email',
                'user_type' => 'required|in:doctor,patient'
            ]);

            // Generate guest token
            $guestToken = $this->getGuestAccessToken(
                $request->user_email,
                $request->user_name
            );

            if (!$guestToken['success']) {
                return response()->json([
                    'error' => 'Failed to generate access token',
                    'details' => $guestToken['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'access_token' => $guestToken['token'],
                'meeting_id' => $meetingId,
                'user_type' => $request->user_type,
                'expires_in' => $guestToken['expires_in']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get join info',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * End call/meeting
     * DELETE /api/meeting/{meetingId}/end
     */
    public function endCall($meetingId)
    {
        try {
            $serviceToken = $this->getServiceAppToken();
            if (!$serviceToken['success']) {
                return response()->json([
                    'error' => 'Failed to get service token'
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceToken['token']
            ])->delete("https://webexapis.com/v1/meetings/{$meetingId}");

            if ($response->successful()) {
                // Update appointment status in database
                // Appointment::where('meeting_id', $meetingId)->update(['status' => 'completed']);

                return response()->json([
                    'success' => true,
                    'message' => 'Call ended successfully'
                ]);
            }

            return response()->json([
                'error' => 'Failed to end call',
                'details' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to end call',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active meetings for a doctor
     * GET /api/doctor/{doctorId}/active-calls
     */
    public function getDoctorActiveCalls($doctorId)
    {
        try {
            // You can fetch from your database or Webex API
            // This is a simplified example
            $serviceToken = $this->getServiceAppToken();
            if (!$serviceToken['success']) {
                return response()->json(['error' => 'Failed to get service token'], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceToken['token']
            ])->get('https://webexapis.com/v1/meetings', [
                'from' => Carbon::now()->subHours(1)->toIso8601String(),
                'to' => Carbon::now()->addHours(2)->toIso8601String()
            ]);

            if ($response->successful()) {
                $meetings = $response->json()['items'] ?? [];

                return response() . json([
                    'success' => true,
                    'active_calls' => $meetings
                ]);
            }

            return response()->json([
                'error' => 'Failed to get active calls'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get active calls',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //guest token


    public function getWebexGuestAccessToken(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:128',
            'user_id' => 'required|string|max:128',
        ]);

        try {
            $issuer = config('services.webex.guest_issuer_id');
            $secret = config('services.webex.guest_secret');

            if (!$issuer || !$secret) {
                return response()->json(['message' => 'Webex guest issuer credentials are not properly configured.'], 500);
            }

            // Decode base64 secret
            $decodedSecret = base64_decode($secret);

            // JWT payload
            $payload = [
                'sub' => $validated['user_id'],
                'name' => $validated['name'],
                'iss'  => $issuer,
                'exp'  => Carbon::now()->addHour()->timestamp,
            ];

            // Create JWT token
            $jwtToken = JWT::encode($payload, $decodedSecret, 'HS256');

            // 🔄 Exchange JWT for Webex access token
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwtToken,
            ])->post('https://webexapis.com/v1/jwt/login');

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Failed to exchange JWT for access token.',
                    'response' => $response->json(),
                ], $response->status());
            }

            return response()->json([
                'jwt_token' => $jwtToken,
                'webex_access_token' => $response->json('token'),
                'expires_in' => $response->json('expiresIn'),
            ]);
        } catch (\Exception $e) {
            Log::error('Webex guest token error: ' . $e->getMessage());

            //this error reponse only for web
            return response()->json([
                'message' => 'An error occurred during Webex guest token generation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeMeeting(Request $request)
    {
        $meetingId = $request->input('meetingId');

        Log::info($meetingId);

        $appointment = Appointment::where('meetingId', $meetingId)->first();
        $user = User::where('id', $appointment->user_id)->first();

        $message = 'Session ended successfully';
        $this->appointment->sendCompletedNotification($appointment);

        return response()->json([
            'status' => true,
            'message' => 'Meeting ID received successfully',
            'data' => $meetingId,
        ]);
    }
}
