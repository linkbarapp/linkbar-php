<?php

declare(strict_types=1);

namespace Linkbar\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct(
        string $message = 'Unauthorized - Invalid API key',
        ?array $responseData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 401, $responseData, $previous);
    }
}
