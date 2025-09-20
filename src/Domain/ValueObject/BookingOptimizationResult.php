<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Representa el resultado de la optimización de reservas.
 * Encapsula métricas y la lista de IDs seleccionados.
 */
final readonly class BookingOptimizationResult
{
    /**
     * @param string[] $requestIds
     */
    public function __construct(
        private array $requestIds,
        private float $totalProfit,
        private float $avgNight,
        private float $minNight,
        private float $maxNight,
    ) {
    }

    /**
     * @return string[]
     */
    public function getRequestIds(): array
    {
        return $this->requestIds;
    }

    public function getTotalProfit(): float
    {
        return $this->totalProfit;
    }

    public function getAvgNight(): float
    {
        return $this->avgNight;
    }

    public function getMinNight(): float
    {
        return $this->minNight;
    }

    public function getMaxNight(): float
    {
        return $this->maxNight;
    }

    /**
     * @return array{
     *   request_ids: string[],
     *   total_profit: float,
     *   avg_night: float,
     *   min_night: float,
     *   max_night: float
     * }
     */
    public function toArray(): array
    {
        return [
            'request_ids' => $this->requestIds,
            'total_profit' => $this->totalProfit,
            'avg_night' => $this->avgNight,
            'min_night' => $this->minNight,
            'max_night' => $this->maxNight,
        ];
    }

    public static function empty(): self
    {
        return new self([], 0.0, 0.0, 0.0, 0.0);
    }
}
