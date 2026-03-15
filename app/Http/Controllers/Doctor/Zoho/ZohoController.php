<?php

namespace App\Http\Controllers\Doctor\Zoho;

use App\Models\Appointment;
use App\Services\ZohoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Repositories\Appointment\AppointmentRepository;
use Illuminate\Support\Facades\Validator;

class ZohoController extends Controller
{
    private AppointmentRepository $appointment;
    protected $webexService;
    protected $zohoService;


    public function __construct(AppointmentRepository $appointmentRepo, ZohoService $zohoService)
    {
        $this->appointment = $appointmentRepo;
        $this->zohoService = $zohoService;
        $this->appointment->setModel(Appointment::make());
    }

    public function redirectToZoho()
    {
        $query = http_build_query([
            'response_type' => 'code',
            'access_type' => 'offline',
            'client_id' => config('services.zoho.client_id'),
            // 'scope' => 'SalesIQ.operators.ALL',
            'scope' => implode(',', [
                'SalesIQ.operators.ALL',
                'SalesIQ.operators.CREATE',
                'SalesIQ.operators.UPDATE',
                'SalesIQ.operators.DELETE',
                // Portals
                'SalesIQ.portals.READ',
                'SalesIQ.portals.CREATE',
                'SalesIQ.portals.UPDATE',

                // Departments
                'SalesIQ.departments.READ',
                'SalesIQ.departments.CREATE',
                'SalesIQ.departments.UPDATE',
                'SalesIQ.departments.DELETE',

                // Lead Scoring
                'SalesIQ.leadscorerules.READ',
                'SalesIQ.leadscorerules.CREATE',
                'SalesIQ.leadscorerules.UPDATE',
                'SalesIQ.leadscorerules.DELETE',
                'SalesIQ.leadscoreconfigs.READ',
                'SalesIQ.leadscoreconfigs.UPDATE',
                'SalesIQ.criteriafields.READ',

                // Visitor Routing
                'SalesIQ.visitorroutingrules.READ',
                'SalesIQ.visitorroutingrules.CREATE',
                'SalesIQ.visitorroutingrules.UPDATE',
                'SalesIQ.visitorroutingrules.DELETE',

                // Chat Routing
                'SalesIQ.chatroutingrules.READ',
                'SalesIQ.chatroutingrules.CREATE',
                'SalesIQ.chatroutingrules.UPDATE',
                'SalesIQ.chatroutingrules.DELETE',

                // Canned Responses
                'SalesIQ.cannedresponses.READ',
                'SalesIQ.cannedresponses.CREATE',
                'SalesIQ.cannedresponses.UPDATE',
                'SalesIQ.cannedresponses.DELETE',

                // Blocked IPs
                'SalesIQ.blockedips.READ',
                'SalesIQ.blockedips.CREATE',
                'SalesIQ.blockedips.UPDATE',
                'SalesIQ.blockedips.DELETE',

                // Chat Monitors
                'SalesIQ.chatmonitors.READ',
                'SalesIQ.chatmonitors.CREATE',
                'SalesIQ.chatmonitors.UPDATE',
                'SalesIQ.chatmonitors.DELETE',
                'SalesIQ.counts.READ',

                // Visitors & Conversations
                'SalesIQ.visitors.READ',
                'SalesIQ.feedbacks.READ',
                'SalesIQ.conversations.READ',
                'SalesIQ.conversations.CREATE',

                // Tracking Presets
                'SalesIQ.trackingpresets.READ',
                'SalesIQ.trackingpresets.CREATE',
                'SalesIQ.trackingpresets.UPDATE',
                'SalesIQ.trackingpresets.DELETE',

                // User Preferences
                'SalesIQ.userpreferences.READ',
                'SalesIQ.userpreferences.UPDATE',

                // Visitor History
                'SalesIQ.visitorhistoryviews.READ',
                'SalesIQ.visitorhistoryviews.CREATE',
                'SalesIQ.visitorhistoryviews.UPDATE',
                'SalesIQ.visitorhistoryviews.DELETE',

                // Intelligent Triggers
                'SalesIQ.triggerrules.READ',
                'SalesIQ.triggerrules.CREATE',
                'SalesIQ.triggerrules.UPDATE',
                'SalesIQ.triggerrules.DELETE',

                // Webhooks
                'SalesIQ.webhooks.READ',
                'SalesIQ.webhooks.CREATE',
                'SalesIQ.webhooks.UPDATE',
                'SalesIQ.webhooks.DELETE',

                // Callbacks
                'SalesIQ.callbacks.UPDATE',

                // Apps
                'SalesIQ.Apps.READ',
                'SalesIQ.Apps.CREATE',
                'SalesIQ.Apps.UPDATE',
                'SalesIQ.Apps.DELETE',

                // Articles
                'SalesIQ.articles.READ',
                'SalesIQ.articles.CREATE',
                'SalesIQ.articles.UPDATE',
                'SalesIQ.articles.DELETE',

                // Encryptions
                'SalesIQ.encryptions.CREATE',

                // Zoho Support
                'ZohoSupport.tickets.CREATE',
                'ZohoSupport.tickets.UPDATE',

            ]),
            'redirect_uri' => config('services.zoho.redirect_uri'),
            'state' => csrf_token(),
            'org_oauth_token' => env('services.zoho.client_org_secret'), // Add this line
        ]);

        return redirect('https://accounts.zoho.com/oauth/v2/auth?' . $query);
    }

    public function handleCallback(Request $request)
    {

        // Verify state token matches
        if ($request->state !== csrf_token()) {
            abort(403, 'Invalid state');
        }

        // Exchange authorization code for tokens
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'code' => $request->code,
            'client_id' => config('services.zoho.client_id'),
            'client_secret' => config('services.zoho.client_secret'),
            'redirect_uri' => route('zoho.callback'),
            'grant_type' => 'authorization_code',
            'scope' => implode(',', [
                'SalesIQ.operators.ALL',       // Manage operators
                'SalesIQ.departments.ALL',     // Manage departments
                'SalesIQ.conversations.ALL',   // Full conversation access
                'SalesIQ.visitorroutingrules.ALL', // NEW REQUIRED SCOPE
            ]),
        ]);

        if ($response->failed()) {
            logger()->error('Zoho token exchange failed', ['response' => $response->json()]);
            return redirect(config('services.zoho.frontend_uri') . '?error=zoho_failed');
        }

        $tokens = $response->json();

        // Only update refresh_token if it exists in response
        $updateData = [
            'access_token' => Crypt::encryptString($tokens['access_token']),
            'expires_at' => now()->addSeconds($tokens['expires_in'] - 60),
            'updated_at' => now(),
        ];

        \Log::info('Zoho response received', [
            'tokens' => $tokens,
        ]);

        if (isset($tokens['refresh_token'])) {
            $updateData['refresh_token'] = Crypt::encryptString($tokens['refresh_token']);
        }

        DB::table('zoho_tokens')->updateOrInsert(
            ['id' => 1],
            $updateData
        );

        return redirect(config('services.zoho.frontend_uri'));
    }

    public function refreshToken()
    {
        $oldToken = DB::table('zoho_tokens')->first();

        if (!$oldToken) {
            return redirect()->route('zoho.redirect');
        }

        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'refresh_token' => Crypt::decryptString($oldToken->refresh_token),
            'client_id' => config('services.zoho.client_id'),
            'client_secret' => config('services.zoho.client_secret'),
            'grant_type' => 'refresh_token',
        ]);

        $tokens = $response->json();

        DB::table('zoho_tokens')->updateOrInsert(
            ['id' => 1],
            [
                'access_token' => Crypt::encryptString($tokens['access_token']),
                'expires_at' => now()->addSeconds($tokens['expires_in'] - 60),
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'Token refreshed');
    }

    public function departments()
    {
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();


        if (!$tokenRecord) {
            throw new \Exception('No Zoho token record found');
        }

        // Decrypt the refresh token
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' .  $accessToken,
            'Accept' => 'application/json'
        ])->get('https://salesiq.zoho.com/api/v2/wellxa/departments');

        if ($response->failed()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $data = $response->json();
        \Log::debug('Zoho Departments', ['data' => $data]);

        if (empty($data)) {
            throw new \Exception('Empty department response');
        }

        return $data;
    }

    public function createDepartments()
    {
        $name = request('name');
        $description = request('description');
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();


        if (!$tokenRecord) {
            throw new \Exception('No Zoho token record found');
        }

        // Decrypt the refresh token
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        $payload = [
            'name' => $name,
            "is_public" => true,
            'description' => $description,
            'operators' => ['1055695000000009001']
        ];


        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://salesiq.zoho.com/api/v2/wellxa/departments', $payload);

        if ($response->failed()) {
            \Log::error('Department creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'payload' => $payload
            ]);
            throw new \Exception('Failed to create department: ' . ($response->json()['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    public function operators()
    {
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();


        if (!$tokenRecord) {
            throw new \Exception('No Zoho token record found');
        }

        // Decrypt the refresh token
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' .  $accessToken,
            'Accept' => 'application/json'
        ])->get('https://salesiq.zoho.com/api/v2/wellxa/operators');

        if ($response->failed()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $data = $response->json();
        \Log::debug('Zoho Operators', ['data' => $data]);

        if (empty($data)) {
            throw new \Exception('Empty Operators response');
        }

        return $data;
    }

    public function createOperators()
    {

        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token record found');
        }

        // Decrypt the refresh token
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        $payload = [
            'email_id' =>  'admin@hospital.com',
            'role' => 'Administrator',
            'is_monitored' => true, // Boolean (not string)
            'is_chat_enabled' => true,
            'first_name' => 'Hospital',
            'last_name' => 'Administrator',
            'departments' => ['1055695000000002022'], // Must be array of strings


        ];

        \Log::debug('Creating Zoho operator', ['payload' => $payload]);

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://salesiq.zoho.com/api/v2/wellxa/operators', $payload);

        if ($response->failed()) {
            \Log::error('Failed to create Zoho operator', [
                'status' => $response->status(),
                'response' => $response->json(),
                'payload' => $payload
            ]);
            throw new \Exception('Failed to create operator: ' . ($response->json()['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }


    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id'     => 'required|string',
            'sender_id'   => 'required|string',
            'is_operator' => 'required|boolean',
            'content'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $payload = [
                'chat_id'     => $request->chat_id,
                'sender_id'   => $request->sender_id,
                'is_operator' => $request->is_operator,
                'content'     => $request->content,
            ];

            \Log::debug('Sending Zoho chat message', ['payload' => $payload]);

            $response = $this->zohoService->sendMessage(
                $request->chat_id,
                $request->sender_id,
                $request->is_operator,
                $request->content
            );

            return $response;
        } catch (\Exception $e) {
            \Log::error('Zoho Chat Send Message Exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getConversations(array $queryParams = [])
    {
        try {
            $tokenRecord = DB::table('zoho_tokens')->latest()->first();
            if (!$tokenRecord) {
                throw new \Exception('No Zoho token record found');
            }

            $accessToken = Crypt::decryptString($tokenRecord->access_token);
            $url = 'https://salesiq.zoho.com/api/v2/wellxa/conversations';

            \Log::debug('Attempting to fetch Zoho conversations', ['url' => $url]);

            // Test DNS resolution first
            if (!gethostbyname('salesiq.zoho.com')) {
                throw new \Exception('DNS resolution failed for salesiq.zoho.com');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type' => 'application/json',
            ])
                ->withOptions(['verify' => true]) // Ensure SSL verification
                ->get($url, $queryParams);

            if ($response->failed()) {
                throw new \Exception('API error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Zoho API Connection Failed', [
                'error' => $e->getMessage(),
                'dns' => gethostbyname('salesiq.zoho.com'),
            ]);
            throw new \Exception('Zoho API request failed: ' . $e->getMessage());
        }
    }

    public function getCompleteConversations($id)
    {
        \Log::info('Zoho get conversation data in getConversation Method :- ', ['conversationId' => $id]);

        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token found');
        }
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        \Log::debug('Zoho Access Token', ['accessToken' => $accessToken]);
        \Log::debug('Fetching Zoho conversation', ['conversationId' => $id]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8'
            ])
                ->timeout(30)
                ->get("https://salesiq.zoho.com/api/v2/wellxa/conversations/{$id}/messages");

            if ($response->failed()) {
                $errorResponse = $response->json();
                \Log::error('Zoho API Error Response', [
                    'error' => $errorResponse,
                    'conversationId' => $id
                ]);
                throw new \Exception('Failed to fetch conversation');
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Zoho Conversation Fetch Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversationId' => $id
            ]);
            throw new \Exception('Conversation fetch failed: ' . $e->getMessage());
        }
    }



    /*******************************************************************/
    /*******************************************************************/
    public function startChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visitor_id' => 'required|string',
            'operator_id' => 'required|string',
            'is_private' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $payload = [
                'visitor_id'  => $request->visitor_id,
                'operator_id' => $request->operator_id,
                'is_private'  => $request->get('is_private', false),
                'custom_data' => $request->get('metadata', []),
            ];

            \Log::debug('Starting Zoho chat', ['payload' => $payload]);

            $response = $this->zohoService->startChat(
                $request->visitor_id,
                $request->operator_id,
                $request->get('is_private', false),
                $request->get('metadata', [])
            );

            return $response;
        } catch (\Exception $e) {
            \Log::error('Zoho Chat Start Exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getOrgAccessToken()
    {

        \Log::info('redirect_uri => ', ['redirect_uri', config('services.zoho.redirect_uri')]);
        return 'https://accounts.zoho.com/oauth/v2/auth?' . http_build_query([
            'scope' => "SalesIQ.operators.ALL SalesIQ.operators.CREATE SalesIQ.operators.UPDATE SalesIQ.operators.DELETE SalesIQ.portals.READ SalesIQ.portals.CREATE SalesIQ.portals.UPDATE SalesIQ.departments.READ SalesIQ.departments.CREATE SalesIQ.departments.UPDATE SalesIQ.departments.DELETE SalesIQ.leadscorerules.READ SalesIQ.leadscorerules.CREATE SalesIQ.leadscorerules.UPDATE SalesIQ.leadscorerules.DELETE SalesIQ.leadscoreconfigs.READ SalesIQ.leadscoreconfigs.UPDATE SalesIQ.criteriafields.READ SalesIQ.visitorroutingrules.READ SalesIQ.visitorroutingrules.CREATE SalesIQ.visitorroutingrules.UPDATE SalesIQ.visitorroutingrules.DELETE SalesIQ.chatroutingrules.READ SalesIQ.chatroutingrules.CREATE SalesIQ.chatroutingrules.UPDATE SalesIQ.chatroutingrules.DELETE SalesIQ.cannedresponses.READ SalesIQ.cannedresponses.CREATE SalesIQ.cannedresponses.UPDATE SalesIQ.cannedresponses.DELETE SalesIQ.blockedips.READ SalesIQ.blockedips.CREATE SalesIQ.blockedips.UPDATE SalesIQ.blockedips.DELETE SalesIQ.chatmonitors.READ SalesIQ.chatmonitors.CREATE SalesIQ.chatmonitors.UPDATE SalesIQ.chatmonitors.DELETE SalesIQ.counts.READ SalesIQ.visitors.READ SalesIQ.feedbacks.READ SalesIQ.conversations.READ SalesIQ.conversations.CREATE SalesIQ.trackingpresets.READ SalesIQ.trackingpresets.CREATE SalesIQ.trackingpresets.UPDATE SalesIQ.trackingpresets.DELETE SalesIQ.userpreferences.READ SalesIQ.userpreferences.UPDATE SalesIQ.visitorhistoryviews.READ SalesIQ.visitorhistoryviews.CREATE SalesIQ.visitorhistoryviews.UPDATE SalesIQ.visitorhistoryviews.DELETE SalesIQ.triggerrules.READ SalesIQ.triggerrules.CREATE SalesIQ.triggerrules.UPDATE SalesIQ.triggerrules.DELETE SalesIQ.webhooks.READ SalesIQ.webhooks.CREATE SalesIQ.webhooks.UPDATE SalesIQ.webhooks.DELETE SalesIQ.callbacks.UPDATE SalesIQ.Apps.READ SalesIQ.Apps.CREATE SalesIQ.Apps.UPDATE SalesIQ.Apps.DELETE SalesIQ.articles.READ SalesIQ.articles.CREATE SalesIQ.articles.UPDATE SalesIQ.articles.DELETE SalesIQ.encryptions.CREATE ZohoSupport.tickets.CREATE ZohoSupport.tickets.UPDATE",
            'client_id' => config('services.zoho.client_org_id'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'redirect_uri' => config('services.zoho.redirect_uri'),
            'prompt' => 'consent'
        ]);

        exit();
        try {
            $requestData = [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.zoho.client_org_id'),
                'client_secret' => config('services.zoho.client_org_secret'),
                'scope' => "SalesIQ.operators.ALL SalesIQ.operators.CREATE SalesIQ.operators.UPDATE SalesIQ.operators.DELETE SalesIQ.portals.READ SalesIQ.portals.CREATE SalesIQ.portals.UPDATE SalesIQ.departments.READ SalesIQ.departments.CREATE SalesIQ.departments.UPDATE SalesIQ.departments.DELETE SalesIQ.leadscorerules.READ SalesIQ.leadscorerules.CREATE SalesIQ.leadscorerules.UPDATE SalesIQ.leadscorerules.DELETE SalesIQ.leadscoreconfigs.READ SalesIQ.leadscoreconfigs.UPDATE SalesIQ.criteriafields.READ SalesIQ.visitorroutingrules.READ SalesIQ.visitorroutingrules.CREATE SalesIQ.visitorroutingrules.UPDATE SalesIQ.visitorroutingrules.DELETE SalesIQ.chatroutingrules.READ SalesIQ.chatroutingrules.CREATE SalesIQ.chatroutingrules.UPDATE SalesIQ.chatroutingrules.DELETE SalesIQ.cannedresponses.READ SalesIQ.cannedresponses.CREATE SalesIQ.cannedresponses.UPDATE SalesIQ.cannedresponses.DELETE SalesIQ.blockedips.READ SalesIQ.blockedips.CREATE SalesIQ.blockedips.UPDATE SalesIQ.blockedips.DELETE SalesIQ.chatmonitors.READ SalesIQ.chatmonitors.CREATE SalesIQ.chatmonitors.UPDATE SalesIQ.chatmonitors.DELETE SalesIQ.counts.READ SalesIQ.visitors.READ SalesIQ.feedbacks.READ SalesIQ.conversations.READ SalesIQ.conversations.CREATE SalesIQ.trackingpresets.READ SalesIQ.trackingpresets.CREATE SalesIQ.trackingpresets.UPDATE SalesIQ.trackingpresets.DELETE SalesIQ.userpreferences.READ SalesIQ.userpreferences.UPDATE SalesIQ.visitorhistoryviews.READ SalesIQ.visitorhistoryviews.CREATE SalesIQ.visitorhistoryviews.UPDATE SalesIQ.visitorhistoryviews.DELETE SalesIQ.triggerrules.READ SalesIQ.triggerrules.CREATE SalesIQ.triggerrules.UPDATE SalesIQ.triggerrules.DELETE SalesIQ.webhooks.READ SalesIQ.webhooks.CREATE SalesIQ.webhooks.UPDATE SalesIQ.webhooks.DELETE SalesIQ.callbacks.UPDATE SalesIQ.Apps.READ SalesIQ.Apps.CREATE SalesIQ.Apps.UPDATE SalesIQ.Apps.DELETE SalesIQ.articles.READ SalesIQ.articles.CREATE SalesIQ.articles.UPDATE SalesIQ.articles.DELETE SalesIQ.encryptions.CREATE ZohoSupport.tickets.CREATE ZohoSupport.tickets.UPDATE",
            ];

            \Log::debug('Attempting to get Org OAuth Token', [
                'request_data' => array_merge($requestData, ['client_secret' => config('services.zoho.client_org_secret')]), // Mask secret
                'timestamp' => now()->toDateTimeString()
            ]);

            $response = Http::asForm()
                ->timeout(30)
                ->retry(3, 1000)
                ->post('https://accounts.zoho.com/oauth/v2/token', $requestData);

            if ($response->failed()) {
                $errorResponse = $response->json();
                $errorCode = $errorResponse['error'] ?? 'unknown_error';
                $errorDescription = $errorResponse['error_description'] ?? 'No error description provided';

                \Log::error('Zoho Org Token Request Failed', [
                    'status_code' => $response->status(),
                    'error_code' => $errorCode,
                    'error_description' => $errorDescription,
                    'request_scope' => $requestData['scope'],
                    'suggested_fix' => $this->getScopeFixSuggestion($errorCode),
                    'full_response' => $errorResponse,
                    'timestamp' => now()->toDateTimeString()
                ]);

                throw new \Exception("Zoho API Error [$errorCode]: $errorDescription");
            }

            $tokens = $response->json();

            \Log::info('Successfully obtained Org OAuth Token', [
                'token_expires_in' => $tokens['expires_in'] ?? null,
                'token_type' => $tokens['token_type'] ?? null,
                'scope' => $tokens['scope'] ?? null,
                'timestamp' => now()->toDateTimeString(),
                'data' => $tokens
            ]);

            return $tokens;
        } catch (\Exception $e) {
            \Log::critical('Critical Failure in getOrgAccessToken', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);

            throw new \Exception('Failed to obtain organization access token: ' . $e->getMessage());
        }
    }

    public function createConversation(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'app_id' => 'required|string',
            'department_id' => 'required|string',
            'question' => 'required|string',
            'visitor.user_id' => 'required|string',
            'visitor.name' => 'sometimes|string',
            'visitor.email' => 'sometimes|email',
            'visitor.phone' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tokenRecord = DB::table('zoho_tokens')->latest()->first();
            if (!$tokenRecord) {
                throw new \Exception('No Zoho token record found');
            }

            // Get Org OAuth token
            $accessToken = "1005.f404490fd680bf18fac34ab1f1778935.667ab850e6a854f750585dbf0e60227d";
            // Prepare payload
            $payload = array_merge($request->all(), [
                'custom_wait_time' => $request->input('custom_wait_time', 60),
                'visitor' => array_merge([
                    'platform' => $request->input('visitor.platform', 'Web'),
                    'current_page' => $request->input('visitor.current_page', url()->current()),
                    'country_code' => $request->input('visitor.country_code', 'US'),
                    'local_time_zone' => $request->input('visitor.local_time_zone', date_default_timezone_get()),
                ], $request->input('visitor', []))
            ]);

            // Remove null values
            $payload = array_filter($payload, function ($value) {
                return $value !== null;
            });

            // Make API request with Org OAuth token
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://salesiq.zoho.com/api/visitor/v1/wellxa/conversations", $payload);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json()
                ]);
            }

            // Handle API errors
            $errorResponse = $response->json();
            \Log::error('Zoho Conversation API Error', [
                'status' => $response->status(),
                'error' => $errorResponse,
                'payload' => $payload
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $errorResponse['message'] ?? 'Failed to create conversation',
                'code' => $errorResponse['code'] ?? $response->status()
            ], $response->status());
        } catch (\Exception $e) {
            \Log::error('Conversation Creation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
