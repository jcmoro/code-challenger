<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ErrorResponseInterface
{
    public function respond(\Throwable $exception): JsonResponse;
}
