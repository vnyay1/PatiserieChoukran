<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    private string $baseUrl;
    private string $userAgent;
    private string $language;
    private string $email;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = env('GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org/search');
        $this->userAgent = env('GEOCODING_USER_AGENT', 'PatisserieChoukran/1.0');
        $this->language = env('GEOCODING_LANGUAGE', 'fr');
        $this->email = env('GEOCODING_EMAIL', '');
        $this->timeout = (int) env('GEOCODING_TIMEOUT', 10);
    }

    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $params = [
            'format' => 'json',
            'q' => $query,
            'limit' => 1,
            'addressdetails' => 0,
        ];

        if ($this->email !== '') {
            $params['email'] = $this->email;
        }

        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept-Language' => $this->language,
        ])->timeout($this->timeout)->get($this->baseUrl, $params);

        if (!$response->ok()) {
            return null;
        }

        $data = $response->json();
        if (!is_array($data) || count($data) === 0) {
            return null;
        }

        $first = $data[0];
        if (!isset($first['lat'], $first['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $first['lat'],
            'lng' => (float) $first['lon'],
            'raw' => $first,
        ];
    }
}
