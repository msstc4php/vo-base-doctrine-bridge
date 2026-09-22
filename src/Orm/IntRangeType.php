<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;

/**
 * Maps {@see IntRange} to one JSON column {min: int, max: int}, following
 * DateRangeType. Unlike BoundedString, IntRange::jsonSerialize() is lossless,
 * so delegating to it is safe here.
 */
final class IntRangeType extends Type
{
    public const string NAME = 'vo_int_range';

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

        if (!$value instanceof IntRange) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', IntRange::class, \get_debug_type($value)));
        }

        return \json_encode($value->jsonSerialize(), \JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IntRange
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string column value, got %s.', \get_debug_type($value)));
        }

        $decoded = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($decoded) || !\is_int($decoded['min'] ?? null) || !\is_int($decoded['max'] ?? null)) {
            throw new InvalidArgumentException(\sprintf('Expected an int-range payload, got "%s".', $value));
        }

        return new IntRange($decoded['min'], $decoded['max']);
    }
}
