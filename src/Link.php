<?php

declare(strict_types=1);

namespace Linkbar;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Linkbar\Exception\HttpException;

class Link
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $longUrl,
        public readonly ?string $keyword,
        public readonly string|array|null $domain,
        public readonly array $tags,
        public readonly int $clickCount,
        public readonly ?DateTimeInterface $createdAt,
        private readonly array $rawData = []
    ) {}

    public function getShortUrl(): ?string
    {
        if ($this->keyword === null) {
            return null;
        }

        $domainName = $this->getDomainName();
        if ($domainName === null) {
            return null;
        }

        return "https://{$domainName}/{$this->keyword}";
    }

    public function getPrettyUrl(): ?string
    {
        if ($this->keyword === null) {
            return null;
        }

        $domainName = $this->getDomainName();
        if ($domainName === null) {
            return null;
        }

        return "{$domainName}/{$this->keyword}";
    }

    public function getDomainName(): ?string
    {
        if (is_array($this->domain)) {
            return $this->domain['name'] ?? null;
        }

        if (is_string($this->domain)) {
            return $this->domain;
        }

        return null;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public static function create(
        string $longUrl,
        ?string $domain = null,
        ?string $keyword = null,
        ?array $tags = null
    ): self {
        $data = ['long_url' => $longUrl];

        if ($domain !== null) {
            $data['domain'] = $domain;
        }

        if ($keyword !== null) {
            $data['keyword'] = $keyword;
        }

        if ($tags !== null) {
            $data['tags'] = $tags;
        }

        $responseData = Linkbar::request('POST', 'links/', $data);

        return self::fromApiData($responseData);
    }

    /**
     * @return Link[]
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public static function getList(?string $search = null): array
    {
        $params = [];
        if ($search !== null) {
            $params['q'] = $search;
        }

        $responseData = Linkbar::request('GET', 'links/', $params);

        // Handle both paginated and non-paginated responses
        $linksData = $responseData['results'] ?? $responseData;
        if (!is_array($linksData)) {
            $linksData = [$linksData];
        }

        return array_map(fn(array $linkData) => self::fromApiData($linkData), $linksData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public static function get(string $linkId): self
    {
        $responseData = Linkbar::request('GET', "links/{$linkId}/");

        return self::fromApiData($responseData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public function update(
        ?string $longUrl = null,
        ?string $domain = null,
        ?string $keyword = null,
        ?array $tags = null
    ): self {
        if ($this->id === null) {
            throw new InvalidArgumentException('Cannot update link without ID.');
        }

        $data = [];
        if ($longUrl !== null) {
            $data['long_url'] = $longUrl;
        }
        if ($domain !== null) {
            $data['domain'] = $domain;
        }
        if ($keyword !== null) {
            $data['keyword'] = $keyword;
        }
        if ($tags !== null) {
            $data['tags'] = $tags;
        }

        $responseData = Linkbar::request('PATCH', "links/{$this->id}/", $data);

        return self::fromApiData($responseData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public function delete(): void
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('Cannot delete link without ID.');
        }

        Linkbar::request('DELETE', "links/{$this->id}/");
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public function refresh(): self
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('Cannot refresh link without ID.');
        }

        $responseData = Linkbar::request('GET', "links/{$this->id}/");

        return self::fromApiData($responseData);
    }

    private static function fromApiData(array $data): self
    {
        $createdAt = null;
        if (isset($data['created_at'])) {
            $createdAt = new DateTimeImmutable($data['created_at']);
        }

        return new self(
            id: $data['id'] ?? null,
            longUrl: $data['long_url'] ?? '',
            keyword: $data['keyword'] ?? null,
            domain: $data['domain'] ?? null,
            tags: $data['tags'] ?? [],
            clickCount: $data['click_count'] ?? 0,
            createdAt: $createdAt,
            rawData: $data
        );
    }

    public function __toString(): string
    {
        $shortUrl = $this->getShortUrl();
        return "Link({$shortUrl} -> {$this->longUrl})";
    }
}