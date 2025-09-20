<?php

declare(strict_types=1);

namespace App\Application\UseCase\CalculateStats;

use App\Application\DTO\BookingRequestDTO;
use App\Domain\Entity\BookingRequest;
use App\Domain\Exception\InvalidBookingRequestException;
use App\Domain\Service\ProfitCalculatorInterface;
use App\Domain\ValueObject\ProfitStats;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final readonly class CalculateStatsUseCase implements CalculateStatsUseCaseInterface
{
    public function __construct(
        private ProfitCalculatorInterface $profitCalculator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param BookingRequestDTO[] $requestsDTO
     * @throws InvalidBookingRequestException
     */
    public function execute(array $requestsDTO): ProfitStats
    {
        $this->logger->debug('Calculating stats for requests', ['count' => count($requestsDTO)]);

        $bookingRequests = array_map(
            fn(BookingRequestDTO $dto): BookingRequest => $this->mapDTOToEntity($dto),
            $requestsDTO
        );

        $result = $this->profitCalculator->calculateStats($bookingRequests);

        $this->logger->debug('Stats calculation completed', $result->toArray());

        return $result;
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
