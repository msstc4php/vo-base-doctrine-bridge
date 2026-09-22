<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use Override;

/**
 * Maps {@see BoundedString} to an embedded sub-document carrying all three
 * fields. BSON stores documents natively, so no JSON encoding is needed.
 *
 * Must NOT delegate to jsonSerialize(): that returns the bare string and drops
 * both bounds, which BoundedString::equals compares.
 */
final class BoundedStringType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_bounded_string';

    /**
     * @return array{value: string, minLength: int, maxLength: int|null}|null
     */
    #[Override]
    public function convertToDatabaseValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof BoundedString) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', BoundedString::class, \get_debug_type($value)));
        }

        return [
            'value' => $value->value,
            'minLength' => $value->minLength,
            'maxLength' => $value->maxLength,
        ];
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?BoundedString
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || !\is_string($value['value'] ?? null) || !\is_int($value['minLength'] ?? null)) {
            throw new InvalidArgumentException(\sprintf('Expected a bounded-string sub-document, got %s.', \get_debug_type($value)));
        }

        $maxLength = $value['maxLength'] ?? null;

        if ($maxLength !== null && !\is_int($maxLength)) {
            throw new InvalidArgumentException(\sprintf('Expected an integer or null maxLength, got %s.', \get_debug_type($maxLength)));
        }

        return new BoundedString($value['value'], $value['minLength'], $maxLength);
    }
}
