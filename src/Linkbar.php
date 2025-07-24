<?php

declare(strict_types=1);

namespace Linkbar;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Linkbar\Exception\BadRequestException;
use Linkbar\Exception\HttpException;
use Linkbar\Exception\NotFoundException;
use Linkbar\Exception\UnauthorizedException;

class Linkbar
{
    private static ?string $apiKey = null;
    private static string $baseUrl = 'https://api.linkbar.co/';
    private static ?Client $httpClient = null;

    public static function setApiKey(?string $apiKey): void
    {
        self::$apiKey = $apiKey;
    }

    public static function getApiKey(): ?string
    {
        return self::$apiKey;
    }

    public static function setBaseUrl(string $baseUrl): void
    {
        self::$baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public static function getBaseUrl(): string
    {
        return self::$baseUrl;
    }

    public static function setHttpClient(Client $client): void
    {
        self::$httpClient = $client;
    }

    public static function getHttpClient(): Client
    {
        if (self::$httpClient === null) {
            self::$httpClient = new Client([
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);
        }

        return self::$httpClient;
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public static function request(
        string $method,
        string $endpoint,
        ?array $data = null
    ): ?array {
        if (self::$apiKey === null) {
            throw new InvalidArgumentException('API key not set. Use Linkbar::setApiKey() to configure.');
        }

        $url = self::$baseUrl . ltrim($endpoint, '/');
        $client = self::getHttpClient();

        $options = [
            'headers' => [
                'X-API-Key' => self::$apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if ($data !== null) {
            if (strtoupper($method) === 'GET') {
                $options['query'] = $data;
            } else {
                $options['json'] = $data;
            }
        }

        try {
            $response = $client->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            
            // Handle empty responses (e.g., from DELETE requests)
            if (empty($body)) {
                return null;
            }
            
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 0;
            $responseBody = $e->getResponse()?->getBody()?->getContents();
            $responseData = null;

            if ($responseBody) {
                try {
                    $responseData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    // Ignore JSON decode errors for response data
                }
            }

            $message = $responseData['message'] ?? $responseData['error'] ?? $e->getMessage();

            match ($statusCode) {
                400 => throw new BadRequestException($message, $responseData, $e),
                401 => throw new UnauthorizedException($message, $responseData, $e),
                404 => throw new NotFoundException($message, $responseData, $e),
                default => throw new HttpException($message, $statusCode, $responseData, $e)
            };
        }
    }
}
