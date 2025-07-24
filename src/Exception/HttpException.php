<?php

declare(strict_types=1);

namespace Linkbar\Exception;

class HttpException extends LinkbarException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?array $responseData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
