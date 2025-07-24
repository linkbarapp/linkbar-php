# Linkbar PHP SDK

[![Tests](https://github.com/linkbarapp/linkbar-php/actions/workflows/tests.yml/badge.svg)](https://github.com/linkbarapp/linkbar-php/actions/workflows/tests.yml)
[![Coverage Status](https://codecov.io/gh/linkbarapp/linkbar-php/branch/main/graph/badge.svg)](https://codecov.io/gh/linkbarapp/linkbar-php)
[![Packagist Version](https://img.shields.io/packagist/v/linkbar/sdk)](https://packagist.org/packages/linkbar/sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/linkbar/sdk)](https://packagist.org/packages/linkbar/sdk)
[![License](https://img.shields.io/github/license/linkbarapp/linkbar-php)](LICENSE)

A modern PHP SDK for the Linkbar API that allows you to create, manage, and track short links. Built with PHP 8.1+ features including named parameters, readonly properties, and match expressions.

## Installation

```bash
composer require linkbar/sdk
```

## Requirements

- PHP 8.1 or higher
- ext-json
- guzzlehttp/guzzle ^7.0

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use Linkbar\Linkbar;
use Linkbar\Link;

// Set your API key
Linkbar::setApiKey('your_api_key_here');

// Create a short link
$link = Link::create('https://example.com', 'linkb.ar');
echo "Created: " . $link->getShortUrl() . "\n";
echo "Redirects to: " . $link->getLongUrl() . "\n";
```

## Authentication

Get your API key from your [Linkbar dashboard](https://app.linkbar.co/api-settings) and configure it:

```php
use Linkbar\Linkbar;

Linkbar::setApiKey('your_api_key_here');
```

You can also configure the base URL if needed (defaults to `https://api.linkbar.co/`):

```php
Linkbar::setBaseUrl('https://api.linkbar.co/');
```

## Working with Links

### Creating Links

```php
use Linkbar\Link;

// Basic link creation
$link = Link::create('https://example.com');

// Create with custom domain using named parameters
$link = Link::create(
    longUrl: 'https://example.com',
    domain: 'linkb.ar'
);

// Create with custom keyword and tags
$link = Link::create(
    longUrl: 'https://example.com',
    domain: 'linkb.ar',
    keyword: 'my-link',
    tags: ['marketing', 'campaign']
);
```

### Accessing Link Properties

```php
$link = Link::create('https://example.com');

echo "ID: " . $link->getId() . "\n";
echo "Short URL: " . $link->getShortUrl() . "\n";
echo "Long URL: " . $link->getLongUrl() . "\n";
echo "Domain: " . $link->getDomainName() . "\n";
echo "Keyword: " . $link->getKeyword() . "\n";
echo "Tags: " . implode(', ', $link->getTags()) . "\n";
echo "Click count: " . $link->getClickCount() . "\n";
echo "Created at: " . $link->getCreatedAt()?->format('Y-m-d H:i:s') . "\n";
```

### Managing Links

```php
// List all links
$links = Link::getList();
foreach ($links as $link) {
    echo $link->getShortUrl() . " -> " . $link->getLongUrl() . "\n";
}

// Search links
$marketingLinks = Link::getList('marketing');

// Get a specific link
$link = Link::get('abc123');

// Update a link
$updatedLink = $link->update(longUrl: 'https://newexample.com');

// Add tags to an existing link
$updatedLink = $link->update(tags: ['updated', 'new-campaign']);

// Delete a link
$link->delete();

// Refresh link data from API
$refreshedLink = $link->refresh();
```

## Working with Domains

### Listing Domains

```php
use Linkbar\Domain;

// List all domains (both custom and Linkbar domains)
$domains = Domain::getList();

// List only custom domains
$customDomains = Domain::getList(isCustom: true);

// Search domains
$domains = Domain::getList(search: 'example');
```

### Creating Custom Domains

```php
// Create a basic custom domain
$domain = Domain::create('example.com');

// Create domain with redirect URLs using named parameters
$domain = Domain::create(
    name: 'example.com',
    homepageRedirectUrl: 'https://example.com',
    nonexistentLinkRedirectUrl: 'https://example.com/404'
);
```

### Managing Domains

```php
// Get a specific domain
$domain = Domain::get('xyz789');

// Access domain properties
echo "Name: " . $domain->getName() . "\n";
echo "Status: " . $domain->getStatus() . "\n";
echo "Is custom: " . ($domain->isCustom() ? 'Yes' : 'No') . "\n";

// Update domain
$updatedDomain = $domain->update(
    homepageRedirectUrl: 'https://newsite.com'
);

// Delete domain
$domain->delete();

// Refresh domain data
$refreshedDomain = $domain->refresh();
```

## Error Handling

The SDK provides specific exception types for different error scenarios:

```php
use Linkbar\Link;
use Linkbar\Exception\UnauthorizedException;
use Linkbar\Exception\BadRequestException;
use Linkbar\Exception\NotFoundException;
use Linkbar\Exception\HttpException;
use InvalidArgumentException;

try {
    $link = Link::create('https://example.com');
    echo "Created: " . $link->getShortUrl();
    
} catch (InvalidArgumentException $e) {
    // API key not set or missing required parameters
    echo "Configuration error: " . $e->getMessage();
    
} catch (UnauthorizedException $e) {
    // Invalid API key
    echo "Authentication error: " . $e->getMessage();
    
} catch (BadRequestException $e) {
    // Invalid request data
    echo "Bad request: " . $e->getMessage();
    if ($e->getResponseData()) {
        print_r($e->getResponseData());
    }
    
} catch (NotFoundException $e) {
    // Resource not found
    echo "Not found: " . $e->getMessage();
    
} catch (HttpException $e) {
    // Other HTTP errors
    echo "HTTP error ({$e->getStatusCode()}): " . $e->getMessage();
    
} catch (\Exception $e) {
    echo "Unexpected error: " . $e->getMessage();
}
```

## Common Use Cases

### Bulk Link Creation

```php
$urlsToShorten = [
    'https://example.com/page1',
    'https://example.com/page2', 
    'https://example.com/page3'
];

$shortenedLinks = [];
foreach ($urlsToShorten as $url) {
    try {
        $link = Link::create(longUrl: $url, domain: 'linkb.ar');
        $shortenedLinks[] = $link;
        echo "✓ {$url} -> " . $link->getShortUrl() . "\n";
    } catch (\Exception $e) {
        echo "✗ Failed to shorten {$url}: " . $e->getMessage() . "\n";
    }
}
```

### Campaign Link Management

```php
// Create campaign links with consistent tagging
$campaignUrls = [
    'https://example.com/landing' => 'landing-page',
    'https://example.com/pricing' => 'pricing-page',
    'https://example.com/signup' => 'signup-page'
];

$campaignLinks = [];
foreach ($campaignUrls as $url => $keyword) {
    $link = Link::create(
        longUrl: $url,
        domain: 'linkb.ar',
        keyword: "summer-{$keyword}",
        tags: ['summer-campaign', '2024']
    );
    $campaignLinks[$keyword] = $link;
}

// Later, check updated click counts
foreach ($campaignLinks as $keyword => $link) {
    $refreshedLink = $link->refresh();
    echo "{$keyword}: " . $refreshedLink->getClickCount() . " clicks\n";
}
```

## API Reference

### Link Methods

- `Link::create(string $longUrl, ?string $domain = null, ?string $keyword = null, ?array $tags = null): Link` - Create a new link
- `Link::getList(?string $search = null): Link[]` - List links with optional search
- `Link::get(string $linkId): Link` - Get a specific link by ID
- `$link->update(?string $longUrl = null, ?string $domain = null, ?string $keyword = null, ?array $tags = null): Link` - Update link
- `$link->delete(): void` - Delete link
- `$link->refresh(): Link` - Refresh link data from API

### Domain Methods

- `Domain::create(string $name, ?string $homepageRedirectUrl = null, ?string $nonexistentLinkRedirectUrl = null): Domain` - Create custom domain
- `Domain::getList(?string $search = null, ?bool $isCustom = null): Domain[]` - List domains with optional filters
- `Domain::get(string $domainId): Domain` - Get a specific domain by ID
- `$domain->update(?string $name = null, ?string $homepageRedirectUrl = null, ?string $nonexistentLinkRedirectUrl = null): Domain` - Update domain
- `$domain->delete(): void` - Delete domain
- `$domain->refresh(): Domain` - Refresh domain data from API

### Link Properties

All properties are readonly and accessible via getter methods:

- `getId(): ?string` - Unique link identifier
- `getShortUrl(): ?string` - Full short URL (e.g., "https://linkb.ar/abc123")
- `getPrettyUrl(): ?string` - Short URL without protocol (e.g., "linkb.ar/abc123")
- `getLongUrl(): string` - Original URL being shortened
- `getKeyword(): ?string` - Short link keyword/slug
- `getDomainName(): ?string` - Domain name as string
- `getTags(): array` - List of tags
- `getClickCount(): int` - Number of clicks (read-only)
- `getCreatedAt(): ?DateTimeInterface` - Creation timestamp
- `getRawData(): array` - Raw API response data

### Domain Properties

All properties are readonly and accessible via getter methods:

- `getId(): ?string` - Unique domain identifier
- `getName(): string` - Domain name
- `isCustom(): bool` - Whether it's a custom domain
- `getStatus(): ?string` - Domain status (pending, connected, disconnected)
- `getOrganization(): string|array|null` - Associated organization
- `getHomepageRedirectUrl(): ?string` - URL for domain root redirects
- `getNonexistentLinkRedirectUrl(): ?string` - URL for 404 redirects
- `getRawData(): array` - Raw API response data

## Configuration

### Custom HTTP Client

You can provide your own Guzzle HTTP client instance:

```php
use GuzzleHttp\Client;
use Linkbar\Linkbar;

$client = new Client([
    'timeout' => 60,
    'verify' => false, // Only for development
]);

Linkbar::setHttpClient($client);
```

## PHP 8.1+ Features

This SDK leverages modern PHP features:

- **Named Parameters**: For clearer method calls
- **Readonly Properties**: Immutable object state
- **Constructor Property Promotion**: Cleaner constructors
- **Match Expressions**: Better error handling
- **Union Types**: Flexible type declarations

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- Documentation: [Linkbar API Docs](https://docs.linkbar.co)
- Issues: [GitHub Issues](https://github.com/linkbarapp/linkbar-php/issues)
- Email: support@linkbar.co