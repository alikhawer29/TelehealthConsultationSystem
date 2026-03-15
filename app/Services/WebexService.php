<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WebexService
{
    protected $client;
    protected $apiUrl;
    protected $accessToken;
    protected $hostBaseUrl;
    protected $guestBaseUrl;

    public function __construct(WebexTokenService $tokenService, $doctorId)
    {
        $this->client = new Client(['timeout' => 30, 'verify' => true]);

        $this->apiUrl = 'https://mtg-broker-a.wbx2.com/api/v2/joseencrypt';
        $this->hostBaseUrl = 'https://instant.webex.com/gen/v1/login?int=jose&v=1&data=';
        $this->guestBaseUrl = 'https://instant.webex.com/gen/v1/talk?int=jose&v=1&data=';

        $this->accessToken = $tokenService->getAccessToken($doctorId);

        // ✅ Log the access token details here
        \Log::info('Webex Access Token for doctor', [
            'doctor_id' => $doctorId,
            'token' => $this->accessToken,
            'is_valid' => !empty($this->accessToken) && strlen($this->accessToken) > 20
        ]);

        if (!$this->accessToken) {
            throw new \Exception('Webex access token not found.');
        }
    }


    public function createInstantConnectMeeting($appointmentId, $doctor, $patient)
    {
        try {
            $payload = [
                // 'access_token' => $this->accessToken,
                'caller' => [
                    'email' => $doctor->email,
                    'name' => $doctor->name,
                ],
                'callee' => [
                    'email' => $patient->email,
                    'name' => $patient->name,
                ],
                'appointment_id' => $appointmentId,
            ];

            $response = $this->client->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (!isset($responseData['data'])) {
                throw new \Exception('Invalid response from Webex API.');
            }

            return [
                'host_join_url' => $this->hostBaseUrl . $responseData['data'],
                'guest_join_url' => $this->guestBaseUrl . $responseData['data'],
            ];
        } catch (\Exception $e) {
            \Log::error('Webex Meeting Creation Failed: ' . $e->getMessage());
            throw new \Exception('Webex meeting creation failed');
        }
    }
}
