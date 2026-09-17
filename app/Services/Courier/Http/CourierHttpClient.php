<?php

namespace App\Services\Courier\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Contracts\CourierConfigInterface;
use App\Exceptions\CourierServiceException;
use Illuminate\Support\Facades\Log;

class CourierHttpClient
{
    public function __construct(
        private CourierConfigInterface $config
    ) {}

    private function buildUrl(string $endpoint): string
    {
        $baseUrl = rtrim((string) $this->config->getBaseUrl(), '/');
        if (empty($baseUrl)) {
            $baseUrl = 'https://portal.packzy.com/api/v1';
        }
        $endpoint = '/' . ltrim($endpoint, '/');
        return $baseUrl . $endpoint;
    }

    public function post(string $endpoint, array $data = []): array
    {
        try {
            $url = $this->buildUrl($endpoint);
            $response = Http::withHeaders($this->config->getHeaders())
                ->timeout(30)
                ->post($url, $data);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            throw new CourierServiceException(
                'HTTP request failed: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function get(string $endpoint): array
    {
        try {
            $url = $this->buildUrl($endpoint);
            $response = Http::withHeaders($this->config->getHeaders())
                ->timeout(30)
                ->get($url);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            throw new CourierServiceException(
                'HTTP request failed: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function handleResponse(Response $response): array
    {
        // Log::debug('Raw API response', [
        //     'status' => $response->status(),
        //     'body' => $response->body()
        // ]);
        if (!$response->successful()) {
            throw new CourierServiceException(
                'API request failed: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();
     //   Log::info()

        if (!is_array($data)) {
            throw new CourierServiceException('Invalid response format');
        }

        return $data;
    }
}
