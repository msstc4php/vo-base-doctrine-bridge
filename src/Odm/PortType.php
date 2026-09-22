<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Network\Port;
use Override;

final class PortType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_port';

    #[Override]
    public function convertToDatabaseValue(mixed $value): ?int
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
    public function convertToPHPValue(mixed $value): ?Port
    {
        if ($value === null) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a numeric database value, got %s.', \get_debug_type($value)));
        }

        return Port::from((int) $value);
    }
}
