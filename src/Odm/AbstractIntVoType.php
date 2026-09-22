<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use Override;

/**
 * Maps any int-backed value object to a BSON integer field. A concrete subclass
 * only names its value-object class.
 *
 * ODM converters take no platform argument and there is no schema declaration.
 */
abstract class AbstractIntVoType extends Type
{
    use ClosureToPHP;

    /**
     * @return class-string<AbstractIntValueObject>
     */
    abstract protected function valueObjectClass(): string;

    #[Override]
    public function convertToDatabaseValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $expected = $this->valueObjectClass();

        if (!$value instanceof $expected) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', $expected, \get_debug_type($value)));
        }

        return $value->value;
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?AbstractIntValueObject
    {
        if ($value === null) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a numeric database value, got %s.', \get_debug_type($value)));
        }

        return ($this->valueObjectClass())::from((int) $value);
    }
}
