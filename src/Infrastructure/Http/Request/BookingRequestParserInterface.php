<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Application\DTO\BookingRequestDTO;
use App\Infrastructure\Exception\ApiException;

interface BookingRequestParserInterface
{
    /**
     * @return BookingRequestDTO[]
     * @throws ApiException
     */
    public function parse(string $jsonPayload): array;
}
