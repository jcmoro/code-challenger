<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Application\DTO\BookingRequestDTO;
use App\Infrastructure\Exception\ApiException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class BookingRequestParser implements BookingRequestParserInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @return BookingRequestDTO[]
     * @throws ApiException
     */
    public function parse(string $jsonPayload): array
    {
        $data = json_decode($jsonPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ApiException::badRequest('Invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw ApiException::badRequest('JSON payload must be an array');
        }

        $dtos = [];
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw ApiException::badRequest(sprintf('Item %d must be an object', $index));
            }

            $dtos[] = $this->parseItem($item, $index);
        }

        return $dtos;
    }

    /**
     * @param array<mixed, mixed> $item
     * @throws ApiException
     */
    private function parseItem(array $item, int $index): BookingRequestDTO
    {
        try {
            /** @var BookingRequestDTO $dto */
            $dto = $this->denormalizer->denormalize(
                $item,
                BookingRequestDTO::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );
        } catch (\Throwable $throwable) {
            throw ApiException::badRequest(sprintf('Item %d invalid data: %s', $index, $throwable->getMessage()));
        }

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = sprintf('Item %d: %s', $index, $violation->getMessage());
            }

            throw ApiException::badRequest(implode(', ', $messages));
        }

        return $dto;
    }
}
