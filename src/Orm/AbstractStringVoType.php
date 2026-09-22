<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use Override;

/**
 * Maps any string-backed value object to a VARCHAR column. A concrete subclass
 * only names its value-object class (and optionally the column length); all
 * conversion is generic via {@see StringConvertible}.
 *
 * DBAL 4: type identity is the name passed to Type::addType — getName() and
 * requiresSQLCommentHint() no longer exist and must not be implemented.
 */
abstract class AbstractStringVoType extends Type
{
    /**
     * @return class-string<StringConvertible>
     */
    abstract protected function valueObjectClass(): string;

    protected function columnLength(): int
    {
        return 255;
    }

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => $this->columnLength()] + $column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
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
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StringConvertible
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string column value, got %s.', \get_debug_type($value)));
        }

        return ($this->valueObjectClass())::from($value);
    }
}
