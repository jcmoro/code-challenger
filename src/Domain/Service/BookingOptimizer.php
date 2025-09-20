<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\BookingRequest;
use Psr\Log\LoggerInterface;

final readonly class BookingOptimizer implements BookingOptimizerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param BookingRequest[] $requests
     * @return array{
     *   request_ids: string[],
     *   total_profit: float,
     *   avg_night: float,
     *   min_night: float,
     *   max_night: float
     * }
     */
    public function findOptimalCombination(array $requests): array
    {
        $this->logger->info('Iniciando optimización de reservas', ['requests_count' => count($requests)]);

        if ($requests === []) {
            $this->logger->info('Array de reservas vacío, retornando resultado por defecto');
            return $this->buildEmptyResult();
        }

        // Ordenar por fecha de check-out
        usort($requests, fn(BookingRequest $a, BookingRequest $b): int => $a->getCheckOut() <=> $b->getCheckOut());

        $n = count($requests);
        $dp = array_fill(0, $n, 0);
        $ids = array_fill(0, $n, []);     // IDs seleccionados

        for ($i = 0; $i < $n; ++$i) {
            $currentProfit = $requests[$i]->getTotalProfit();
            $currentIds = [$requests[$i]->getRequestId()];

            $latestIndex = $this->findLatestNonConflictingIndex($requests, $i);
            if ($latestIndex !== null) {
                $currentProfit += $dp[$latestIndex];
                $currentIds = array_merge($ids[$latestIndex], $currentIds);
            }

            // Elegir mejor entre tomar o no tomar la reserva actual
            if ($i > 0 && $dp[$i - 1] > $currentProfit) {
                $dp[$i] = $dp[$i - 1];
                $ids[$i] = $ids[$i - 1];
            } else {
                $dp[$i] = $currentProfit;
                $ids[$i] = $currentIds;
            }
        }

        $optimalIds = $ids[$n - 1];
        $optimalRequests = array_filter(
            $requests,
            fn(BookingRequest $r): bool => in_array($r->getRequestId(), $optimalIds, true)
        );

        return $this->buildResult($optimalRequests);
    }

    /**
     * Encuentra el índice de la última reserva no conflictiva usando búsqueda binaria.
     * Devuelve null si no hay ninguna.
     *
     * @param BookingRequest[] $requests
     */
    private function findLatestNonConflictingIndex(array $requests, int $currentIndex): ?int
    {
        $low = 0;
        $high = $currentIndex - 1;
        $currentRequest = $requests[$currentIndex];
        $result = null;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            if ($requests[$mid]->getCheckOut() <= $currentRequest->getCheckIn()) {
                $result = $mid;
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return $result;
    }

    /**
     * @param BookingRequest[] $selectedRequests
     * @return array{
     *   request_ids: string[],
     *   total_profit: float,
     *   avg_night: float,
     *   min_night: float,
     *   max_night: float
     * }
     */
    private function buildResult(array $selectedRequests): array
    {
        if ($selectedRequests === []) {
            return $this->buildEmptyResult();
        }

        $requestIds = array_map(fn($r): string => $r->getRequestId(), $selectedRequests);
        $profits = array_map(fn($r): float => $r->getTotalProfit(), $selectedRequests);
        $profitsPerNight = array_map(fn($r): float => $r->getProfitPerNight(), $selectedRequests);

        return [
            'request_ids' => $requestIds,
            'total_profit' => round(array_sum($profits), 2),
            'avg_night' => round(array_sum($profitsPerNight) / count($profitsPerNight), 2),
            'min_night' => round(min($profitsPerNight), 2),
            'max_night' => round(max($profitsPerNight), 2),
        ];
    }

    /**
     * @return array{
     *    request_ids: string[],
     *    total_profit: float,
     *    avg_night: float,
     *    min_night: float,
     *    max_night: float
     *  }
     */
    private function buildEmptyResult(): array
    {
        return [
            'request_ids' => [],
            'total_profit' => 0,
            'avg_night' => 0,
            'min_night' => 0,
            'max_night' => 0,
        ];
    }
}
