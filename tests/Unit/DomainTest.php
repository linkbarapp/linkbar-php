<?php

declare(strict_types=1);

namespace Linkbar\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Linkbar\Domain;
use Linkbar\Linkbar;
use PHPUnit\Framework\TestCase;

class DomainTest extends TestCase
{
    private array $sampleDomainData = [
        'id' => 'domain123',
        'name' => 'example.com',
        'is_custom' => true,
        'status' => 'connected',
        'organization' => ['id' => 'org123', 'name' => 'Test Org'],
        'homepage_redirect_url' => 'https://example.com',
        'nonexistent_link_redirect_url' => 'https://example.com/404'
    ];

    protected function setUp(): void
    {
        Linkbar::setApiKey('test-api-key');
        Linkbar::setBaseUrl('https://api.linkbar.co/');
    }

    public function testDomainConstruction(): void
    {
        $domain = new Domain(
            id: 'domain123',
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: ['id' => 'org123'],
            homepageRedirectUrl: 'https://example.com',
            nonexistentLinkRedirectUrl: 'https://example.com/404',
            rawData: $this->sampleDomainData
        );

        $this->assertSame('domain123', $domain->getId());
        $this->assertSame('example.com', $domain->getName());
        $this->assertTrue($domain->isCustom());
        $this->assertSame('connected', $domain->getStatus());
        $this->assertSame(['id' => 'org123'], $domain->getOrganization());
        $this->assertSame('https://example.com', $domain->getHomepageRedirectUrl());
        $this->assertSame('https://example.com/404', $domain->getNonexistentLinkRedirectUrl());
        $this->assertSame($this->sampleDomainData, $domain->getRawData());
    }

    public function testCreateDomain(): void
    {
        $mockHandler = new MockHandler([
            new Response(201, [], json_encode($this->sampleDomainData))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domain = Domain::create(
            'example.com',
            'https://example.com',
            'https://example.com/404'
        );

        $this->assertSame('domain123', $domain->getId());
        $this->assertSame('example.com', $domain->getName());
        $this->assertTrue($domain->isCustom());
        $this->assertSame('connected', $domain->getStatus());
        $this->assertSame('https://example.com', $domain->getHomepageRedirectUrl());
        $this->assertSame('https://example.com/404', $domain->getNonexistentLinkRedirectUrl());
    }

    public function testCreateDomainWithMinimalData(): void
    {
        $minimalData = [
            'id' => 'domain123',
            'name' => 'example.com',
            'is_custom' => true,
            'status' => 'pending'
        ];

        $mockHandler = new MockHandler([
            new Response(201, [], json_encode($minimalData))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domain = Domain::create('example.com');

        $this->assertSame('domain123', $domain->getId());
        $this->assertSame('example.com', $domain->getName());
        $this->assertTrue($domain->isCustom());
        $this->assertSame('pending', $domain->getStatus());
        $this->assertNull($domain->getHomepageRedirectUrl());
        $this->assertNull($domain->getNonexistentLinkRedirectUrl());
    }

    public function testGetList(): void
    {
        $responseData = [
            'results' => [
                $this->sampleDomainData,
                array_merge($this->sampleDomainData, [
                    'id' => 'domain456', 
                    'name' => 'another.com',
                    'is_custom' => false
                ])
            ]
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($responseData))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domains = Domain::getList('example', true);

        $this->assertCount(2, $domains);
        $this->assertInstanceOf(Domain::class, $domains[0]);
        $this->assertSame('domain123', $domains[0]->getId());
        $this->assertSame('domain456', $domains[1]->getId());
        $this->assertTrue($domains[0]->isCustom());
        $this->assertFalse($domains[1]->isCustom());
    }

    public function testGetListWithoutPagination(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([$this->sampleDomainData]))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domains = Domain::getList();

        $this->assertCount(1, $domains);
        $this->assertInstanceOf(Domain::class, $domains[0]);
        $this->assertSame('domain123', $domains[0]->getId());
    }

    public function testGet(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($this->sampleDomainData))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domain = Domain::get('domain123');

        $this->assertSame('domain123', $domain->getId());
        $this->assertSame('example.com', $domain->getName());
        $this->assertTrue($domain->isCustom());
    }

    public function testUpdate(): void
    {
        $updatedData = array_merge($this->sampleDomainData, [
            'name' => 'updated.com',
            'homepage_redirect_url' => 'https://updated.com'
        ]);

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($updatedData))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domain = new Domain(
            id: 'domain123',
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: 'https://example.com',
            nonexistentLinkRedirectUrl: null
        );

        $updatedDomain = $domain->update(
            name: 'updated.com',
            homepageRedirectUrl: 'https://updated.com'
        );

        $this->assertSame('updated.com', $updatedDomain->getName());
        $this->assertSame('https://updated.com', $updatedDomain->getHomepageRedirectUrl());
    }

    public function testUpdateThrowsExceptionWithoutId(): void
    {
        $domain = new Domain(
            id: null,
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot update domain without ID.');

        $domain->update(name: 'updated.com');
    }

    public function testDelete(): void
    {
        $mockHandler = new MockHandler([
            new Response(204, [])
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domain = new Domain(
            id: 'domain123',
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $domain->delete(); // Should not throw
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function testDeleteThrowsExceptionWithoutId(): void
    {
        $domain = new Domain(
            id: null,
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete domain without ID.');

        $domain->delete();
    }

    public function testRefresh(): void
    {
        $refreshedData = array_merge($this->sampleDomainData, ['status' => 'verified']);

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($refreshedData))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        Linkbar::setHttpClient($client);

        $domain = new Domain(
            id: 'domain123',
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $refreshedDomain = $domain->refresh();

        $this->assertSame('verified', $refreshedDomain->getStatus());
    }

    public function testRefreshThrowsExceptionWithoutId(): void
    {
        $domain = new Domain(
            id: null,
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot refresh domain without ID.');

        $domain->refresh();
    }

    public function testToString(): void
    {
        $domain = new Domain(
            id: 'domain123',
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: null,
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $this->assertSame('Domain(example.com)', (string) $domain);
    }

    public function testOrganizationAsString(): void
    {
        $domain = new Domain(
            id: 'domain123',
            name: 'example.com',
            isCustom: true,
            status: 'connected',
            organization: 'Simple Org Name',
            homepageRedirectUrl: null,
            nonexistentLinkRedirectUrl: null
        );

        $this->assertSame('Simple Org Name', $domain->getOrganization());
    }
}