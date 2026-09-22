<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use Override;

/**
 * Maps any string-backed value object to a BSON string field. A concrete
 * subclass only names its value-object class; all conversion is generic via
 * {@see StringConvertible}.
 *
 * ODM converters take no platform argument (unlike DBAL) and there is no
 * schema declaration method.
 */
abstract class AbstractStringVoType extends Type
{
    use ClosureToPHP;

    /**
     * @return class-string<StringConvertible>
     */
    abstract protected function valueObjectClass(): string;

    #[Override]
    public function convertToDatabaseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof StringConvertible) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', StringConvertible::class, \get_debug_type($value)));
        }

        return (string) $value;
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?StringConvertible
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string database value, got %s.', \get_debug_type($value)));
        }

        return ($this->valueObjectClass())::from($value);
    }
}
