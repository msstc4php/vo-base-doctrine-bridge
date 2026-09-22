<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use Override;

/**
 * Maps {@see Percentage} to a floating-point column.
 *
 * Deliberately not a DECIMAL declaration: DBAL 4 requires explicit precision
 * and scale for decimals, and any pair chosen here would silently round a
 * Percentage carrying a longer fraction.
 */
final class PercentageType extends Type
{
    public const string NAME = 'vo_percentage';

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getFloatDeclarationSQL($column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?float
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
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Percentage
    {
        if ($value === null) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a numeric column value, got %s.', \get_debug_type($value)));
        }

        return Percentage::from((float) $value);
    }
}
