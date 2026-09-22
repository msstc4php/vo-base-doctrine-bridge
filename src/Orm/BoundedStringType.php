<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use Override;

/**
 * Maps {@see BoundedString} to one JSON column carrying all three fields.
 *
 * The bounds are part of the value's identity — BoundedString::equals compares
 * them — so a scalar column would not round-trip. This type must NOT delegate
 * to jsonSerialize(): that returns the bare string and drops both bounds.
 * IntRangeType may delegate; this one may not.
 */
final class BoundedStringType extends Type
{
    public const string NAME = 'vo_bounded_string';

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

        if (!$value instanceof BoundedString) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', BoundedString::class, \get_debug_type($value)));
        }

        return \json_encode([
            'value' => $value->value,
            'minLength' => $value->minLength,
            'maxLength' => $value->maxLength,
        ], \JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BoundedString
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string column value, got %s.', \get_debug_type($value)));
        }

        $decoded = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($decoded) || !\is_string($decoded['value'] ?? null) || !\is_int($decoded['minLength'] ?? null)) {
            throw new InvalidArgumentException(\sprintf('Expected a bounded-string payload, got "%s".', $value));
        }

        $maxLength = $decoded['maxLength'] ?? null;

        if ($maxLength !== null && !\is_int($maxLength)) {
            throw new InvalidArgumentException(\sprintf('Expected an integer or null maxLength, got %s.', \get_debug_type($maxLength)));
        }

        return new BoundedString($decoded['value'], $decoded['minLength'], $maxLength);
    }
}
