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
     * Microseconds plus a numeric offset. The core's jsonSerialize() uses ATOM,
     * which carries neither -- a DateRange with microseconds or a named zone was
     * written happily and came back different, equals() returning false.
     *
     * The offset is deliberate rather than the zone name: local time plus a zone
     * name is ambiguous in the repeated hour of a DST fall-back, and restoring
     * from it picks the first of the two instants. Measured on the second
     * 01:30:00.123456 in America/New_York, that shifts the value back one hour;
     * the offset form round-trips both instants exactly. DateRange::equals()
     * compares instants, so the offset is sufficient.
     */
    private const string BOUNDARY_FORMAT = 'Y-m-d\TH:i:s.uP';

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

        return [
            'start' => $value->start->format(self::BOUNDARY_FORMAT),
            'end' => $value->end->format(self::BOUNDARY_FORMAT),
        ];
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

        if (!\is_string($value['start'] ?? null) || !\is_string($value['end'] ?? null)) {
            throw new InvalidArgumentException('Expected a date-range sub-document with string start and end.');
        }

        return new DateRange(new DateTimeImmutable($value['start']), new DateTimeImmutable($value['end']));
    }
}
