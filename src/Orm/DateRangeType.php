<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use Override;

/**
 * Maps {@see DateRange} to a single JSON text column ({"start":..,"end":..}).
 * When the endpoints must be queryable, map two DATETIME columns instead of
 * using this type.
 */
final class DateRangeType extends Type
{
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
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateRange) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', DateRange::class, \get_debug_type($value)));
        }

        return \json_encode([
            'start' => $value->start->format(self::BOUNDARY_FORMAT),
            'end' => $value->end->format(self::BOUNDARY_FORMAT),
        ], \JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateRange
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string column value, got %s.', \get_debug_type($value)));
        }

        $decoded = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($decoded) || !\is_string($decoded['start'] ?? null) || !\is_string($decoded['end'] ?? null)) {
            throw new InvalidArgumentException(\sprintf('Expected a date-range payload with string start and end, got "%s".', $value));
        }

        return new DateRange(new DateTimeImmutable($decoded['start']), new DateTimeImmutable($decoded['end']));
    }
}
