<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class ProfitStats
{
    public function __construct(
        public float $avgNight,
        public float $minNight,
        public float $maxNight
    ) {
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0, 0.0);
    }

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'avg_night' => $this->avgNight,
            'min_night' => $this->minNight,
            'max_night' => $this->maxNight,
        ];
    }
}
