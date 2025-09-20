<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class ApiException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function badRequest(string $message): self
    {
        return new self($message, Response::HTTP_BAD_REQUEST);
    }
}
