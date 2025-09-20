<?php

declare(strict_types=1);

namespace App\Application\UseCase\MaximizeProfits;

use App\Domain\ValueObject\BookingOptimizationResult;
use Psr\Log\NullLogger;
use App\Application\DTO\BookingRequestDTO;
use App\Domain\Entity\BookingRequest;
use App\Domain\Exception\InvalidBookingRequestException;
use App\Domain\Service\BookingOptimizerInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final readonly class MaximizeProfitsUseCase implements MaximizeProfitsUseCaseInterface
{
    public function __construct(
        private BookingOptimizerInterface $bookingOptimizer,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * @param BookingRequestDTO[] $requestsDTO
     * @throws InvalidBookingRequestException
     */
    public function execute(array $requestsDTO): BookingOptimizationResult
    {
        $this->logger->debug('Maximizing profits for requests', ['count' => count($requestsDTO)]);

        try {
            $bookingRequests = array_map(
                fn(BookingRequestDTO $dto): BookingRequest => $this->mapDTOToEntity($dto),
                $requestsDTO
            );

            $result = $this->bookingOptimizer->findOptimalCombination($bookingRequests);

            $this->logger->debug('Profit maximization completed', $result->toArray());

            return $result;
        } catch (InvalidBookingRequestException $invalidBookingRequestException) {
            $this->logger->warning('Domain validation error during profit maximization', [
                'error' => $invalidBookingRequestException->getMessage()
            ]);
            throw $invalidBookingRequestException;
        }
    }

    private function mapDTOToEntity(BookingRequestDTO $dto): BookingRequest
    {
        try {
            return new BookingRequest(
                requestId: $dto->requestId,
                checkIn: new DateTimeImmutable($dto->checkIn),
                nights: $dto->nights,
                sellingRate: $dto->sellingRate,
                margin: $dto->margin
            );
        } catch (\Exception) {
            throw InvalidBookingRequestException::invalidCheckInDate($dto->checkIn);
        }
    }
}
