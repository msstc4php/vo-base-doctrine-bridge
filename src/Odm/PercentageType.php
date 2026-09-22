<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use Override;

/** Maps {@see Percentage} to a BSON double. */
final class PercentageType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_percentage';

    #[Override]
    public function convertToDatabaseValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Percentage) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', Percentage::class, \get_debug_type($value)));
        }

        return $value->value;
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?Percentage
    {
        if ($value === null) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a numeric database value, got %s.', \get_debug_type($value)));
        }

        return Percentage::from((float) $value);
    }
}
