<?php

declare(strict_types=1);

namespace Linkbar\Tests\Integration;

use Linkbar\Domain;
use Linkbar\Exception\UnauthorizedException;
use Linkbar\Link;
use Linkbar\Linkbar;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Linkbar SDK
 * 
 * These tests require a valid API key and will make real API calls.
 * Set LINKBAR_API_KEY environment variable to run these tests.
 * 
 * To run: LINKBAR_API_KEY=your_key phpunit tests/Integration/
 */
class LinkbarIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $apiKey = $_ENV['LINKBAR_API_KEY'] ?? null;
        
        if ($apiKey === null) {
            $this->markTestSkipped('LINKBAR_API_KEY environment variable not set');
        }
        
        Linkbar::setApiKey($apiKey);
        Linkbar::setBaseUrl('https://api.linkbar.co/');
    }

    public function testInvalidApiKeyThrowsException(): void
    {
        Linkbar::setApiKey('invalid-key');
        
        $this->expectException(UnauthorizedException::class);
        
        Link::getList();
    }

    public function testLinkLifecycle(): void
    {
        // Create a link
        $originalUrl = 'https://example.com/test-' . time();
        $link = Link::create($originalUrl);
        
        $this->assertNotNull($link->getId());
        $this->assertSame($originalUrl, $link->getLongUrl());
        $this->assertNotNull($link->getShortUrl());
        $this->assertSame(0, $link->getClickCount());
        
        // Update the link
        $newUrl = 'https://updated.com/test-' . time();
        $updatedLink = $link->update(longUrl: $newUrl, tags: ['integration-test']);
        
        $this->assertSame($newUrl, $updatedLink->getLongUrl());
        $this->assertContains('integration-test', $updatedLink->getTags());
        
        // Refresh the link
        $refreshedLink = $link->refresh();
        $this->assertSame($newUrl, $refreshedLink->getLongUrl());
        
        // Get the specific link
        $linkId = $link->getId();
        $this->assertNotNull($linkId, 'Link ID should not be null');
        $fetchedLink = Link::get($linkId);
        $this->assertSame($link->getId(), $fetchedLink->getId());
        $this->assertSame($newUrl, $fetchedLink->getLongUrl());
        
        // Delete the link
        $link->delete();
        
        // Verify it was deleted by trying to fetch it
        $linkId = $link->getId();
        $this->assertNotNull($linkId, 'Link ID should not be null before attempting to fetch');
        $this->expectException(\Linkbar\Exception\NotFoundException::class);
        Link::get($linkId);
    }

    public function testLinkListing(): void
    {
        // Create a test link with unique tag
        $testTag = 'integration-test-' . time();
        $link = Link::create(
            longUrl: 'https://example.com/list-test',
            tags: [$testTag]
        );
        
        // List all links
        $allLinks = Link::getList();
        $this->assertIsArray($allLinks);
        $this->assertNotEmpty($allLinks);
        
        // Search for our specific link
        $searchResults = Link::getList($testTag);
        $this->assertIsArray($searchResults);
        
        // Find our link in the results
        $foundLink = null;
        foreach ($searchResults as $searchedLink) {
            if ($searchedLink->getId() === $link->getId()) {
                $foundLink = $searchedLink;
                break;
            }
        }
        
        $this->assertNotNull($foundLink, 'Created link should be found in search results');
        $this->assertContains($testTag, $foundLink->getTags());
        
        // Clean up
        $link->delete();
    }

    public function testDomainListing(): void
    {
        // List all domains
        $allDomains = Domain::getList();
        $this->assertIsArray($allDomains);
        
        // Should have at least some default domains
        $this->assertNotEmpty($allDomains);
        
        // Test filtering by custom domains
        $customDomains = Domain::getList(isCustom: true);
        $this->assertIsArray($customDomains);
        
        // All returned domains should be custom
        foreach ($customDomains as $domain) {
            $this->assertTrue($domain->isCustom(), 'All domains in custom filter should be custom');
        }
        
        // Test filtering by non-custom domains  
        $nonCustomDomains = Domain::getList(isCustom: false);
        $this->assertIsArray($nonCustomDomains);
        
        // All returned domains should be non-custom
        foreach ($nonCustomDomains as $domain) {
            $this->assertFalse($domain->isCustom(), 'All domains in non-custom filter should be non-custom');
        }
    }

    public function testCreateLinkWithDomain(): void
    {
        // Get available domains
        $domains = Domain::getList();
        $this->assertNotEmpty($domains, 'Should have at least one domain available');
        
        // Use the first available domain
        $domain = $domains[0];
        
        // Create link with specific domain
        $link = Link::create(
            longUrl: 'https://example.com/domain-test-' . time(),
            domain: $domain->getName()
        );
        
        $this->assertSame($domain->getName(), $link->getDomainName());
        $this->assertStringContainsString($domain->getName(), $link->getShortUrl());
        
        // Clean up
        $link->delete();
    }

    public function testErrorHandling(): void
    {
        // Test creating link with invalid URL
        try {
            Link::create('not-a-valid-url');
            $this->fail('Should throw exception for invalid URL');
        } catch (\Linkbar\Exception\BadRequestException $e) {
            $this->assertStringContainsString('url', strtolower($e->getMessage()));
        }
        
        // Test getting non-existent link
        try {
            Link::get('non-existent-id-' . time());
            $this->fail('Should throw exception for non-existent link');
        } catch (\Linkbar\Exception\NotFoundException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
}