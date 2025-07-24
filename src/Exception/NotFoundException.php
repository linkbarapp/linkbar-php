<?php

declare(strict_types=1);

namespace Linkbar\Exception;

class NotFoundException extends HttpException
{
    public function __construct(
        string $message = 'Not Found - Resource does not exist',
        ?array $responseData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 404, $responseData, $previous);
    }
}
