<?php

namespace App\Domain\SpecDocItem\ValueObject;

use App\Domain\ValueObjectInterface\IntValue;
use InvalidArgumentException;

/**
 * Status VO
 */
final class StatusId implements IntValue
{
    public const STATUSES = [
        0 => 'Pending',
        1 => 'OK',
        2 => 'NG',
    ];

    public function __construct(private int $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function validate(int $value): void
    {
        if (!array_key_exists($value, self::STATUSES)) {
            throw new InvalidArgumentException(__CLASS__ . ' must be a defined number.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
