<?php

declare(strict_types=1);

namespace Linkbar;

use InvalidArgumentException;
use Linkbar\Exception\HttpException;

class Domain
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $name,
        public readonly bool $isCustom,
        public readonly ?string $status,
        public readonly string|array|null $organization,
        public readonly ?string $homepageRedirectUrl,
        public readonly ?string $nonexistentLinkRedirectUrl,
        private readonly array $rawData = []
    ) {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isCustom(): bool
    {
        return $this->isCustom;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getOrganization(): string|array|null
    {
        return $this->organization;
    }

    public function getHomepageRedirectUrl(): ?string
    {
        return $this->homepageRedirectUrl;
    }

    public function getNonexistentLinkRedirectUrl(): ?string
    {
        return $this->nonexistentLinkRedirectUrl;
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
        string $name,
        ?string $homepageRedirectUrl = null,
        ?string $nonexistentLinkRedirectUrl = null
    ): self {
        $data = ['name' => $name];

        if ($homepageRedirectUrl !== null) {
            $data['homepage_redirect_url'] = $homepageRedirectUrl;
        }

        if ($nonexistentLinkRedirectUrl !== null) {
            $data['nonexistent_link_redirect_url'] = $nonexistentLinkRedirectUrl;
        }

        $responseData = Linkbar::request('POST', 'domains/', $data);

        return self::fromApiData($responseData);
    }

    /**
     * @return Domain[]
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public static function getList(?string $search = null, ?bool $isCustom = null): array
    {
        $params = [];
        if ($search !== null) {
            $params['q'] = $search;
        }
        if ($isCustom !== null) {
            $params['is_custom'] = $isCustom ? 'true' : 'false';
        }

        $responseData = Linkbar::request('GET', 'domains/', $params);

        // Handle both paginated and non-paginated responses
        $domainsData = $responseData['results'] ?? $responseData;
        if (!is_array($domainsData)) {
            $domainsData = [$domainsData];
        }

        return array_map(fn(array $domainData) => self::fromApiData($domainData), $domainsData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public static function get(string $domainId): self
    {
        $responseData = Linkbar::request('GET', "domains/{$domainId}/");

        return self::fromApiData($responseData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public function update(
        ?string $name = null,
        ?string $homepageRedirectUrl = null,
        ?string $nonexistentLinkRedirectUrl = null
    ): self {
        if ($this->id === null) {
            throw new InvalidArgumentException('Cannot update domain without ID.');
        }

        $data = [];
        if ($name !== null) {
            $data['name'] = $name;
        }
        if ($homepageRedirectUrl !== null) {
            $data['homepage_redirect_url'] = $homepageRedirectUrl;
        }
        if ($nonexistentLinkRedirectUrl !== null) {
            $data['nonexistent_link_redirect_url'] = $nonexistentLinkRedirectUrl;
        }

        $responseData = Linkbar::request('PATCH', "domains/{$this->id}/", $data);

        return self::fromApiData($responseData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public function delete(): void
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('Cannot delete domain without ID.');
        }

        Linkbar::request('DELETE', "domains/{$this->id}/");
    }

    /**
     * @throws InvalidArgumentException
     * @throws HttpException
     */
    public function refresh(): self
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('Cannot refresh domain without ID.');
        }

        $responseData = Linkbar::request('GET', "domains/{$this->id}/");

        return self::fromApiData($responseData);
    }

    private static function fromApiData(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? '',
            isCustom: $data['is_custom'] ?? false,
            status: $data['status'] ?? null,
            organization: $data['organization'] ?? null,
            homepageRedirectUrl: $data['homepage_redirect_url'] ?? null,
            nonexistentLinkRedirectUrl: $data['nonexistent_link_redirect_url'] ?? null,
            rawData: $data
        );
    }

    public function __toString(): string
    {
        return "Domain({$this->name})";
    }
}