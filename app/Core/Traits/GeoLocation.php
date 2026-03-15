<?php

namespace App\Core\Traits;

use GuzzleHttp\Client;

trait GeoLocation
{

    public function getGeolocationData($lat, $lng)
    {
        try {
            $client = new Client();
            $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => $lat . ',' . $lng,
                    'key' => config('services.google_maps.api_key'),
                ]
            ]);

            $geocodeData = json_decode($response->getBody(), true);

            if (isset($geocodeData['status']) && $geocodeData['status'] === 'REQUEST_DENIED') {
                throw new \Exception($geocodeData['error_message']);
            }

            return $geocodeData;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

