<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Network\Port;
use Override;

final class PortType extends Type
{
    public const string NAME = 'vo_port';

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Port) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', Port::class, \get_debug_type($value)));
        }

        return $value->value;
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Port
    {
        if ($value === null) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a numeric column value, got %s.', \get_debug_type($value)));
        }

        return Port::from((int) $value);
    }
}
