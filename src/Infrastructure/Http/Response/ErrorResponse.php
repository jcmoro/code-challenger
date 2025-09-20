<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Response;

use App\Domain\Exception\InvalidBookingRequestException;
use App\Infrastructure\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponse implements ErrorResponseInterface
{
    public function respond(\Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof ApiException => new JsonResponse(
                ['error' => $exception->getMessage()],
                $exception->getStatusCode()
            ),
            $exception instanceof InvalidBookingRequestException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            ),
            default => new JsonResponse(
                ['error' => 'Internal server error'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ),
        };
    }
}
