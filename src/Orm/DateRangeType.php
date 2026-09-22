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

        return \json_encode($value->jsonSerialize(), \JSON_THROW_ON_ERROR);
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

        /** @var array{start: string, end: string} $decoded */
        $decoded = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        return new DateRange(new DateTimeImmutable($decoded['start']), new DateTimeImmutable($decoded['end']));
    }
}
