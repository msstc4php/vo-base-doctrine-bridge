<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use Override;

/**
 * Maps {@see DateRange} to an embedded sub-document {start: string, end: string}
 * of ISO 8601 instants. BSON stores arrays natively, so no JSON encoding is
 * needed.
 */
final class DateRangeType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_date_range';

    /**
     * @return array{start: string, end: string}|null
     */
    #[Override]
    public function convertToDatabaseValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateRange) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', DateRange::class, \get_debug_type($value)));
        }

        return $value->jsonSerialize();
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?DateRange
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || !isset($value['start'], $value['end']) || !\is_string($value['start']) || !\is_string($value['end'])) {
            throw new InvalidArgumentException(\sprintf('Expected an array with string "start" and "end" keys, got %s.', \get_debug_type($value)));
        }

        return new DateRange(new DateTimeImmutable($value['start']), new DateTimeImmutable($value['end']));
    }
}
