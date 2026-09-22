<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;

/**
 * Maps {@see IntRange} to an embedded sub-document {min: int, max: int},
 * following DateRangeType. IntRange::jsonSerialize() is lossless, so
 * delegating to it is safe -- unlike BoundedString.
 */
final class IntRangeType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_int_range';

    /**
     * @return array{min: int, max: int}|null
     */
    #[Override]
    public function convertToDatabaseValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof IntRange) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', IntRange::class, \get_debug_type($value)));
        }

        return $value->jsonSerialize();
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?IntRange
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || !\is_int($value['min'] ?? null) || !\is_int($value['max'] ?? null)) {
            throw new InvalidArgumentException(\sprintf('Expected an int-range sub-document, got %s.', \get_debug_type($value)));
        }

        return new IntRange($value['min'], $value['max']);
    }
}
