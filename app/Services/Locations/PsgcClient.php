<?php

namespace App\Services\Locations;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

class PsgcClient
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function provinces(): array
    {
        return $this->getJsonList('/provinces');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function citiesMunicipalities(string $provinceCode): array
    {
        return $this->getJsonList("/provinces/{$provinceCode}/cities-municipalities");
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function barangays(string $cityMunicipalityCode): array
    {
        return $this->getJsonList("/cities-municipalities/{$cityMunicipalityCode}/barangays");
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getJsonList(string $path): array
    {
        $baseUrl = rtrim((string) config('services.psgc.base_url', 'https://psgc.cloud/api'), '/');
        $timeout = max(10, (int) config('services.psgc.timeout', 30));
        $url = $baseUrl.'/'.ltrim($path, '/');

        $response = $this->http
            ->acceptJson()
            ->timeout($timeout)
            ->retry(
                6,
                900,
                function (\Throwable $exception): bool {
                    if ($exception instanceof RequestException) {
                        $status = $exception->response?->status();

                        return in_array($status, [429, 500, 502, 503, 504], true);
                    }

                    return true;
                },
            )
            ->get($url)
            ->throw();

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }
}

