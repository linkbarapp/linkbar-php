<?php

declare(strict_types=1);

namespace Linkbar\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Linkbar\Exception\BadRequestException;
use Linkbar\Exception\HttpException;
use Linkbar\Exception\NotFoundException;
use Linkbar\Exception\UnauthorizedException;
use Linkbar\Linkbar;
use PHPUnit\Framework\TestCase;

class LinkbarTest extends TestCase
{
    protected function setUp(): void
    {
        Linkbar::setApiKey(null);
        Linkbar::setBaseUrl('https://api.linkbar.co/');
        Linkbar::setHttpClient(new Client());
    }

    public function testSetAndGetApiKey(): void
    {
        $apiKey = 'test-api-key';
        Linkbar::setApiKey($apiKey);
        
        $this->assertSame($apiKey, Linkbar::getApiKey());
    }

    public function testSetAndGetBaseUrl(): void
    {
        $baseUrl = 'https://custom.api.com/';
        Linkbar::setBaseUrl($baseUrl);
        
        $this->assertSame($baseUrl, Linkbar::getBaseUrl());
    }

    public function testSetAndGetHttpClient(): void
    {
        $client = new Client(['timeout' => 60]);
        Linkbar::setHttpClient($client);
        
        $this->assertSame($client, Linkbar::getHttpClient());
    }

    public function testRequestThrowsExceptionWhenApiKeyNotSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key not set. Use Linkbar::setApiKey() to configure.');
        
        Linkbar::request('GET', 'test');
    }

    public function testSuccessfulGetRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode(['success' => true]))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);
        Linkbar::setApiKey('test-key');
        
        $result = Linkbar::request('GET', 'test', ['param' => 'value']);
        
        $this->assertSame(['success' => true], $result);
    }

    public function testSuccessfulPostRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(201, [], json_encode(['created' => true]))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);
        Linkbar::setApiKey('test-key');
        
        $result = Linkbar::request('POST', 'test', ['data' => 'value']);
        
        $this->assertSame(['created' => true], $result);
    }

    public function testUnauthorizedExceptionThrown(): void
    {
        $responseBody = json_encode(['message' => 'Invalid API key']);
        $mockHandler = new MockHandler([
            new RequestException(
                'Unauthorized',
                new Request('GET', 'test'),
                new Response(401, [], $responseBody)
            )
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);
        Linkbar::setApiKey('invalid-key');
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid API key');
        
        Linkbar::request('GET', 'test');
    }

    public function testBadRequestExceptionThrown(): void
    {
        $responseBody = json_encode(['message' => 'Invalid data']);
        $mockHandler = new MockHandler([
            new RequestException(
                'Bad Request',
                new Request('POST', 'test'),
                new Response(400, [], $responseBody)
            )
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);
        Linkbar::setApiKey('test-key');
        
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid data');
        
        Linkbar::request('POST', 'test');
    }

    public function testNotFoundExceptionThrown(): void
    {
        $responseBody = json_encode(['message' => 'Resource not found']);
        $mockHandler = new MockHandler([
            new RequestException(
                'Not Found',
                new Request('GET', 'test'),
                new Response(404, [], $responseBody)
            )
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);
        Linkbar::setApiKey('test-key');
        
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Resource not found');
        
        Linkbar::request('GET', 'test');
    }

    public function testGenericHttpExceptionThrown(): void
    {
        $responseBody = json_encode(['message' => 'Server error']);
        $mockHandler = new MockHandler([
            new RequestException(
                'Internal Server Error',
                new Request('GET', 'test'),
                new Response(500, [], $responseBody)
            )
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);
        Linkbar::setApiKey('test-key');
        
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Server error');
        
        try {
            Linkbar::request('GET', 'test');
        } catch (HttpException $e) {
            $this->assertSame(500, $e->getStatusCode());
            $this->assertSame(['message' => 'Server error'], $e->getResponseData());
            throw $e;
        }
    }

    public function testBaseUrlNormalization(): void
    {
        Linkbar::setBaseUrl('https://api.example.com');
        $this->assertSame('https://api.example.com/', Linkbar::getBaseUrl());
        
        Linkbar::setBaseUrl('https://api.example.com/');
        $this->assertSame('https://api.example.com/', Linkbar::getBaseUrl());
    }
}