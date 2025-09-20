<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * @implements IteratorAggregate<int, BookingRequestDTO>
 */
final class BookingRequestDTOCollection implements Countable, IteratorAggregate
{
    /** @var BookingRequestDTO[] */
    private array $items;

    /**
     * @param BookingRequestDTO[] $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            if (!$item instanceof BookingRequestDTO) {
                throw new \InvalidArgumentException('All items must be instances of BookingRequestDTO');
            }
        }

        $this->items = array_values($items);
    }

    /**
     * @return BookingRequestDTO[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<int, BookingRequestDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function add(BookingRequestDTO $dto): void
    {
        $this->items[] = $dto;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
