<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class ZohoService
{
    public function getAccessToken()
    {
        // Get the latest token record from database
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();

        if (!$tokenRecord) {
            throw new \Exception('No Zoho token record found');
        }

        // Decrypt the refresh token
        $refreshToken = Crypt::decryptString($tokenRecord->refresh_token);

        // Request new access token
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'refresh_token' => $refreshToken,
            'client_id' => config('services.zoho.client_id'),
            'client_secret' => config('services.zoho.client_secret'),
            'grant_type' => 'refresh_token'
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to refresh Zoho access token');
        }
        $tokens = $response->json();
        // Update the database with new access token
        DB::table('zoho_tokens')
            ->where('id', $tokenRecord->id)
            ->update([
                'access_token' => Crypt::encryptString($tokens['access_token']),
                'expires_at' => now()->addSeconds($tokens['expires_in']),
                'updated_at' => now()
            ]);

        return $tokens['access_token'];
    }

    public function createVisitor($user)
    {
        \Log::info('Zoho visitor data in createVisitor Method :- ', [$user]);

        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token found');
        }
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        // Build the payload with proper formatting
        $payload = [
            'name' => $user['name'],
            'email' => $user['email'],
            'meta' => [
                'user_id' => (string)$user['id'],
                'role' => $user['role']
            ]
        ];

        \Log::debug('Zoho Access Token', ['accessToken' => $accessToken]);
        \Log::debug('Creating Zoho visitor', ['payload' => $payload]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8'  // Explicit charset
            ])
                ->timeout(30)
                ->post('https://salesiq.zoho.com/api/v2/wellxa/visitors', $payload);

            if ($response->failed()) {
                $errorResponse = $response->json();
                \Log::error('Zoho API Error Response', [
                    'error' => $errorResponse,
                    'request_payload' => $payload
                ]);
                throw new \Exception('Failed to create visitor');
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Zoho Visitor Creation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            throw new \Exception('Visitor creation failed: ' . $e->getMessage());
        }
    }

    protected function getDepartmentId($role)
    {
        try {
            $response = $this->getZohoDepartments();
            \Log::debug('Zoho departments response', ['response' => $response]);

            $departmentName = $this->mapDepartment($role);
            \Log::debug('Mapped department name', ['role' => $role, 'departmentName' => $departmentName]);

            // Handle different response structures
            $departments = $response['data'] ?? $response['departments'] ?? $response;

            foreach ((array)$departments as $dept) {
                if (isset($dept['name']) && strtolower($dept['name']) === strtolower($departmentName)) {
                    return (string)$dept['id']; // Ensure string format
                }
            }
        } catch (\Exception $e) {
            \Log::error('Department fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Fallback with proper error handling
        $fallbackId = '38000000000017';
        \Log::warning('Using fallback department ID', [
            'role' => $role,
            'fallbackId' => $fallbackId
        ]);

        return $fallbackId;
    }

    public function getZohoDepartments()
    {
        // Get the latest token record from database
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();

        if (!$tokenRecord) {
            throw new \Exception('No Zoho token record found');
        }

        // Decrypt the refresh token
        $refreshToken = Crypt::decryptString($tokenRecord->access_token);

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' .  $refreshToken,
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


    public function createOperator($user)
    {
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token found');
        }
        $accessToken = Crypt::decryptString($tokenRecord->access_token); // Add this line

        // $departmentId = $this->getDepartmentId($user['role']);

        // $departmentName = $this->mapDepartment($user['role']);
        // $departmentId = $this->ensureDepartmentExists($departmentName);

        $payload = [
            'email_id' => $user['email'],
            'role' => $this->mapRoleToZoho($user['role']),
            'departments' => ['1055695000000002022'], // Must be array of strings
            // 'departments' => [$departmentId], // Must be array of strings
            'is_monitored' => true, // Boolean (not string)
            'is_chat_enabled' => true,
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'] ?? ''
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

    public function startChat($visitorId, $operatorId, $isPrivate = false, $metadata = [])
    {
        try {
            $url = 'https://salesiq.zoho.com/api/v2/wellxa/conversations'; // Correct endpoint

            $payload = [
                'visitor_id' => $visitorId,
                'operator_id' => $operatorId,
                'is_private' => $isPrivate,
                'custom_data' => $metadata
            ];

            \Log::debug('Starting Zoho chat', ['url' => $url, 'payload' => $payload]);

            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $this->getAccessToken(),
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            if ($response->failed()) {
                throw new \Exception('API error: ' . $response->body());
            }

            return $response;
        } catch (\Exception $e) {
            \Log::error('Zoho Chat Start Failed', [
                'error' => $e->getMessage(),
                'visitor_id' => $visitorId,
                'operator_id' => $operatorId
            ]);
            throw new \Exception('Zoho SalesIQ API request failed: ' . $e->getMessage());
        }
    }

    public function sendMessage($chatId, $senderId, $isOperator, $content)
    {
        // Convert to proper types
        $isOperator = filter_var($isOperator, FILTER_VALIDATE_BOOLEAN);
        $senderId = (string)$senderId;
        $url = "https://salesiq.zoho.com/api/v2/wellxa/conversations/{$chatId}/messages";

        // Zoho requires different payload structure for visitors vs operators
        $payload = ['text' => $content];
        \Log::info('Zoho Message Send => ', ['text' => $content]);

        \Log::debug('Final Zoho Message Attempt', [
            'url' => $url,
            'payload' => $payload,
            'is_operator' => $isOperator,
            'token' => $this->getAccessToken(),
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->failed()) {
                $error = $response->json();
                \Log::error('Zoho API Detailed Error', [
                    'status' => $response->status(),
                    'error' => $error,
                    'request' => [
                        'url' => $url,
                        'payload' => $payload
                    ]
                ]);

                throw new \Exception($error['error']['message'] ?? 'API request failed');
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Final Message Send Failure', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'sender_id' => $senderId,
                'is_operator' => $isOperator,
                'payload_structure' => $payload
            ]);
            throw new \Exception('Message sending failed: ' . $e->getMessage());
        }
    }

    public function getChatMessages($chatId)
    {
        return Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $this->getAccessToken()
        ])->get("https://salesiq.zoho.com/api/v2/wellxa/chats/{$chatId}/messages")->json();
    }


    protected function mapRoleToZoho($role)
    {
        return match (strtolower($role)) {
            'admin' => 'Administrator',
            'doctor', 'nurse', 'physician', 'user' => 'Associate',
            default => 'Associate'
        };
    }

    protected function mapDepartment($role)
    {
        return match (strtolower($role)) {
            'doctor' => 'Medical',
            'nurse' => 'Nursing',
            'physician' => 'Therapy',
            'admin', 'administrator' => 'Administration',
            'user' => 'Patient',
            default => 'General'
        };
    }

    public function createDepartment($name, $description = '')
    {
        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token found');
        }
        $accessToken = Crypt::decryptString($tokenRecord->access_token); // Add this line

        $payload = [
            'name' => $name,
            'description' => $description,
            'is_enabled' => true
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

    protected function ensureDepartmentExists($departmentName)
    {
        try {
            // Check if department exists
            $departments = $this->getZohoDepartments();
            foreach ($departments['data'] as $dept) {
                if (strcasecmp($dept['name'], $departmentName) === 0) {
                    return $dept['id'];
                }
            }

            // Create if doesn't exist
            $newDept = $this->createDepartment($departmentName);
            return $newDept['data']['id'];
        } catch (\Exception $e) {
            \Log::error('Department verification failed', ['error' => $e]);
            throw new \Exception('Failed to verify/create department');
        }
    }

    protected function createZohoDepartment($name)
    {
        $accessToken = $this->getZohoAccessToken();

        $payload = [
            'name' => $name,
            'is_public' => true,
            'description' => $name . ' Department',
            'operators' => [$this->getFallbackOperatorId()]
        ];

        $response = Http::withToken($accessToken)
            ->post('https://salesiq.zoho.com/api/v2/{portal_name}/departments', $payload); // Replace {portal_name}

        if ($response->successful()) {
            return $response->json()['data']['id'] ?? null;
        }

        \Log::error('Failed to create department', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return null;
    }

    protected function getFallbackDepartmentId()
    {
        return '6000000000017'; // Your default existing department ID
    }

    protected function getFallbackOperatorId()
    {
        return '6000000000033'; // An existing operator ID (admin/system) for department creation
    }

    protected function getOrCreateDepartmentId($role)
    {
        try {
            $departments = $this->getZohoDepartments();
            \Log::debug('Zoho departments', ['departments' => $departments]);

            $departmentName = $this->mapDepartment($role);
            \Log::debug('Target department name', ['departmentName' => $departmentName]);

            // Try to find department by name
            foreach ($departments['data'] ?? [] as $dept) {
                if (strtolower($dept['name']) === strtolower($departmentName)) {
                    return $dept['id'];
                }
            }

            // If not found, create a new department
            $newDepartmentId = $this->createZohoDepartment($departmentName);
            if ($newDepartmentId) {
                return $newDepartmentId;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch or create department', ['error' => $e->getMessage()]);
        }

        // Fallback
        return $this->getFallbackDepartmentId();
    }

    public function getConversation($conversationId)
    {
        \Log::info('Zoho get conversation data in getConversation Method :- ', ['conversationId' => $conversationId]);

        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token found');
        }
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        \Log::debug('Zoho Access Token', ['accessToken' => $accessToken]);
        \Log::debug('Fetching Zoho conversation', ['conversationId' => $conversationId]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8'
            ])
                ->timeout(30)
                ->get("https://salesiq.zoho.com/api/v2/wellxa/conversations/{$conversationId}");

            if ($response->failed()) {
                $errorResponse = $response->json();
                \Log::error('Zoho API Error Response', [
                    'error' => $errorResponse,
                    'conversationId' => $conversationId
                ]);
                throw new \Exception('Failed to fetch conversation');
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Zoho Conversation Fetch Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversationId' => $conversationId
            ]);
            throw new \Exception('Conversation fetch failed: ' . $e->getMessage());
        }
    }

    public function closeConversation($conversationId)
    {
        \Log::info('Zoho close conversation data in closeConversation Method :- ', [
            'conversationId' => $conversationId,
        ]);

        $tokenRecord = DB::table('zoho_tokens')->latest()->first();
        if (!$tokenRecord) {
            throw new \Exception('No Zoho token found');
        }
        $accessToken = Crypt::decryptString($tokenRecord->access_token);

        \Log::debug('Zoho Access Token', ['accessToken' => $accessToken]);
        \Log::debug('Closing Zoho conversation', [
            'conversationId' => $conversationId
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8'
            ])
                ->timeout(30)
                ->post("https://salesiq.zoho.com/api/v2/wellxa/conversations/{$conversationId}/close");

            if ($response->failed()) {
                $errorResponse = $response->json();
                \Log::error('Zoho API Error Response', [
                    'error' => $errorResponse,
                    'conversationId' => $conversationId
                ]);
                throw new \Exception('Failed to close conversation');
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('Zoho Conversation Close Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversationId' => $conversationId
            ]);
            throw new \Exception('Conversation close failed: ' . $e->getMessage());
        }
    }
}
