<?php

namespace App\Services;

use App\Models\WebexToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WebexTokenService
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct()
    {
        // $this->clientId = config('services.webex.client_id');
        // $this->clientSecret = config('services.webex.client_secret');
        // $this->redirectUri = config('services.webex.redirect_uri');

        $this->clientId = 'Cf6cf968c8d0e64b2469570d7dcfdcbe7a64540ca27780389499e5f31668d4def';
        $this->clientSecret = '815a97ac7a0a2497e913256fb00562992969285dfacaefb65ec409114e295848';
        $this->redirectUri = 'http://localhost/telehealth_backend/doctor-api/webex/callback';
    }

    public function getAccessToken($doctorId)
    {
        $token = WebexToken::where('doctor_id', $doctorId)->first();

        if (!$token) {
            throw new \Exception("Webex token not found for doctor ID: $doctorId");
        }

        // Check if expired
        if (Carbon::now()->gte($token->expires_at)) {
            return $this->refreshAccessToken($token);
        }

        return $token->access_token;
    }

    protected function refreshAccessToken(WebexToken $token)
    {
        $client = new Client();

        try {
            $response = $client->post('https://webexapis.com/v1/access_token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $token->refresh_token,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $token->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('Failed to refresh Webex token: ' . $e->getMessage());
            throw new \Exception('Could not refresh Webex token.');
        }
    }
}
