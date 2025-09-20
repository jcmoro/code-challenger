<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\ValueObject\BookingOptimizationResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de dominio que resuelve el problema de interval scheduling con pesos:
 * encuentra la combinación de reservas no solapadas que maximiza el beneficio.
 */
final readonly class BookingOptimizer implements BookingOptimizerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param BookingRequest[] $requests
     */
    public function findOptimalCombination(array $requests): BookingOptimizationResult
    {
        $this->logger->info('Iniciando optimización de reservas', [
            'requests_count' => count($requests),
        ]);

        if ($requests === []) {
            $this->logger->info('Array de reservas vacío, retornando resultado vacío');
            return BookingOptimizationResult::empty();
        }

        // Ordenamos las reservas por fecha de checkout para aplicar mejor beneficio
        usort($requests, fn(BookingRequest $a, BookingRequest $b): int => $a->getCheckOut() <=> $b->getCheckOut());

        $count = count($requests);
        $maxProfitAtIndex = array_fill(0, $count, 0.0);
        $selectedIdsAtIndex = array_fill(0, $count, []);

        for ($i = 0; $i < $count; ++$i) {
            $currentProfit = $requests[$i]->getTotalProfit();
            $currentIds = [$requests[$i]->getRequestId()];

            $latestIndex = $this->findLatestNonConflictingIndex($requests, $i);
            if ($latestIndex !== null) {
                $currentProfit += $maxProfitAtIndex[$latestIndex];
                $currentIds = array_merge($selectedIdsAtIndex[$latestIndex], $currentIds);
            }

            // Decidir mejor beneficio: incluir o excluir la reserva actual
            if ($i > 0 && $maxProfitAtIndex[$i - 1] > $currentProfit) {
                $maxProfitAtIndex[$i] = $maxProfitAtIndex[$i - 1];
                $selectedIdsAtIndex[$i] = $selectedIdsAtIndex[$i - 1];
            } else {
                $maxProfitAtIndex[$i] = $currentProfit;
                $selectedIdsAtIndex[$i] = $currentIds;
            }
        }

        $optimalIds = $selectedIdsAtIndex[$count - 1];
        $optimalRequests = array_filter(
            $requests,
            fn(BookingRequest $r): bool => in_array($r->getRequestId(), $optimalIds, true)
        );

        return $this->buildResult($optimalRequests);
    }

    /**
     * Encuentra mediante búsqueda binaria el índice de la última reserva
     * que no solapa con la actual. Devuelve null si no hay ninguna.
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
     * Construye el VO de resultado a partir de las reservas seleccionadas.
     *
     * @param BookingRequest[] $selectedRequests
     */
    private function buildResult(array $selectedRequests): BookingOptimizationResult
    {
        if ($selectedRequests === []) {
            return BookingOptimizationResult::empty();
        }

        $requestIds = array_map(fn(BookingRequest $r): string => $r->getRequestId(), $selectedRequests);
        $profits = array_map(fn(BookingRequest $r): float => $r->getTotalProfit(), $selectedRequests);
        $profitsPerNight = array_map(fn(BookingRequest $r): float => $r->getProfitPerNight(), $selectedRequests);

        return new BookingOptimizationResult(
            requestIds: $requestIds,
            totalProfit: round(array_sum($profits), 2),
            avgNight: round(array_sum($profitsPerNight) / count($profitsPerNight), 2),
            minNight: round(min($profitsPerNight), 2),
            maxNight: round(max($profitsPerNight), 2),
        );
    }
}
