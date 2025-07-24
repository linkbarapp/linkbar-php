<?php

declare(strict_types=1);

namespace Linkbar\Tests\Unit;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Linkbar\Link;
use Linkbar\Linkbar;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    private array $sampleLinkData = [
        'id' => 'abc123',
        'long_url' => 'https://example.com',
        'keyword' => 'test-link',
        'domain' => ['name' => 'linkb.ar'],
        'tags' => ['test', 'example'],
        'click_count' => 42,
        'created_at' => '2024-01-01T12:00:00Z'
    ];

    protected function setUp(): void
    {
        Linkbar::setApiKey('test-api-key');
        Linkbar::setBaseUrl('https://api.linkbar.co/');
    }

    public function testLinkConstruction(): void
    {
        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: ['name' => 'linkb.ar'],
            tags: ['tag1', 'tag2'],
            clickCount: 10,
            createdAt: new DateTimeImmutable('2024-01-01T12:00:00Z'),
            rawData: $this->sampleLinkData
        );

        $this->assertSame('abc123', $link->getId());
        $this->assertSame('https://example.com', $link->getLongUrl());
        $this->assertSame('test', $link->getKeyword());
        $this->assertSame('linkb.ar', $link->getDomainName());
        $this->assertSame(['tag1', 'tag2'], $link->getTags());
        $this->assertSame(10, $link->getClickCount());
        $this->assertInstanceOf(DateTimeImmutable::class, $link->getCreatedAt());
        $this->assertSame($this->sampleLinkData, $link->getRawData());
    }

    public function testGetShortUrl(): void
    {
        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: ['name' => 'linkb.ar'],
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->assertSame('https://linkb.ar/test', $link->getShortUrl());
    }

    public function testGetPrettyUrl(): void
    {
        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: ['name' => 'linkb.ar'],
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->assertSame('linkb.ar/test', $link->getPrettyUrl());
    }

    public function testGetShortUrlWithStringDomain(): void
    {
        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'custom.com',
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->assertSame('https://custom.com/test', $link->getShortUrl());
        $this->assertSame('custom.com/test', $link->getPrettyUrl());
        $this->assertSame('custom.com', $link->getDomainName());
    }

    public function testGetShortUrlReturnsNullWhenNoKeyword(): void
    {
        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: null,
            domain: ['name' => 'linkb.ar'],
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->assertNull($link->getShortUrl());
        $this->assertNull($link->getPrettyUrl());
    }

    public function testCreateLink(): void
    {
        $mockHandler = new MockHandler([
            new Response(201, [], json_encode($this->sampleLinkData) ?: '')
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $link = Link::create('https://example.com', 'linkb.ar', 'test-link', ['test']);

        $this->assertSame('abc123', $link->getId());
        $this->assertSame('https://example.com', $link->getLongUrl());
        $this->assertSame('test-link', $link->getKeyword());
        $this->assertSame('linkb.ar', $link->getDomainName());
        $this->assertSame(['test', 'example'], $link->getTags());
        $this->assertSame(42, $link->getClickCount());
    }

    public function testGetList(): void
    {
        $responseData = [
            'results' => [
                $this->sampleLinkData,
                array_merge($this->sampleLinkData, ['id' => 'def456', 'keyword' => 'another'])
            ]
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($responseData) ?: '')
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $links = Link::getList('search-term');

        $this->assertCount(2, $links);
        $this->assertInstanceOf(Link::class, $links[0]);
        $this->assertSame('abc123', $links[0]->getId());
        $this->assertSame('def456', $links[1]->getId());
    }

    public function testGetListWithoutPagination(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([$this->sampleLinkData]) ?: '')
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $links = Link::getList();

        $this->assertCount(1, $links);
        $this->assertInstanceOf(Link::class, $links[0]);
        $this->assertSame('abc123', $links[0]->getId());
    }

    public function testGet(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($this->sampleLinkData) ?: '')
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $link = Link::get('abc123');

        $this->assertSame('abc123', $link->getId());
        $this->assertSame('https://example.com', $link->getLongUrl());
    }

    public function testUpdate(): void
    {
        $updatedData = array_merge($this->sampleLinkData, [
            'long_url' => 'https://updated.com',
            'tags' => ['updated']
        ]);

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($updatedData) ?: '')
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'linkb.ar',
            tags: ['old'],
            clickCount: 0,
            createdAt: null
        );

        $updatedLink = $link->update(
            longUrl: 'https://updated.com',
            tags: ['updated']
        );

        $this->assertSame('https://updated.com', $updatedLink->getLongUrl());
        $this->assertSame(['updated'], $updatedLink->getTags());
    }

    public function testUpdateThrowsExceptionWithoutId(): void
    {
        $link = new Link(
            id: null,
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'linkb.ar',
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot update link without ID.');

        $link->update(longUrl: 'https://updated.com');
    }

    public function testDelete(): void
    {
        $mockHandler = new MockHandler([
            new Response(204, [])
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'linkb.ar',
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $link->delete(); // Should not throw
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function testDeleteThrowsExceptionWithoutId(): void
    {
        $link = new Link(
            id: null,
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'linkb.ar',
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete link without ID.');

        $link->delete();
    }

    public function testRefresh(): void
    {
        $refreshedData = array_merge($this->sampleLinkData, ['click_count' => 100]);

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($refreshedData) ?: '')
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'linkb.ar',
            tags: [],
            clickCount: 42,
            createdAt: null
        );

        $refreshedLink = $link->refresh();

        $this->assertSame(100, $refreshedLink->getClickCount());
    }

    public function testRefreshThrowsExceptionWithoutId(): void
    {
        $link = new Link(
            id: null,
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: 'linkb.ar',
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot refresh link without ID.');

        $link->refresh();
    }

    public function testToString(): void
    {
        $link = new Link(
            id: 'abc123',
            longUrl: 'https://example.com',
            keyword: 'test',
            domain: ['name' => 'linkb.ar'],
            tags: [],
            clickCount: 0,
            createdAt: null
        );

        $this->assertSame('Link(https://linkb.ar/test -> https://example.com)', (string) $link);
    }
}