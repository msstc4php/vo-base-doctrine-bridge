<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use Override;

/**
 * Maps {@see GeoCoordinates} to an embedded sub-document {lat: float, lng: float}.
 * BSON stores arrays natively, so no JSON encoding is needed.
 */
final class GeoCoordinatesType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_geo_coordinates';

    /**
     * @return array{lat: float, lng: float}|null
     */
    #[Override]
    public function convertToDatabaseValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof GeoCoordinates) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', GeoCoordinates::class, \get_debug_type($value)));
        }

        return $value->toArray();
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?GeoCoordinates
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || !isset($value['lat'], $value['lng']) || !\is_numeric($value['lat']) || !\is_numeric($value['lng'])) {
            throw new InvalidArgumentException(\sprintf('Expected an array with numeric "lat" and "lng" keys, got %s.', \get_debug_type($value)));
        }

        return GeoCoordinates::fromFloats((float) $value['lat'], (float) $value['lng']);
    }
}
