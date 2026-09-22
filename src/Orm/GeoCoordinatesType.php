<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use Override;

/**
 * Maps {@see GeoCoordinates} to a single JSON text column ({"lat":..,"lng":..}).
 * When the sub-fields must be queryable, map the value object as an
 * `#[ORM\Embeddable]` with a column per coordinate instead of using this type.
 */
final class GeoCoordinatesType extends Type
{
    public const string NAME = 'vo_geo_coordinates';

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

        if (!$value instanceof GeoCoordinates) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', GeoCoordinates::class, \get_debug_type($value)));
        }

        return \json_encode($value->toArray(), \JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?GeoCoordinates
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string column value, got %s.', \get_debug_type($value)));
        }

        /** @var array{lat: float, lng: float} $decoded */
        $decoded = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        return GeoCoordinates::fromFloats($decoded['lat'], $decoded['lng']);
    }
}
