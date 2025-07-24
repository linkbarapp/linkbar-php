<?php

declare(strict_types=1);

namespace Linkbar\Exception;

class BadRequestException extends HttpException
{
    public function __construct(
        string $message = 'Bad Request - Invalid request data',
        ?array $responseData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 400, $responseData, $previous);
    }
}