<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Brick\Money\Money;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use Override;

/**
 * Maps a {@see Money} amount to a BSON string field in the compact
 * "CURRENCY amount" form (e.g. "USD 100.00").
 */
final class BrickMoneyType extends Type
{
    use ClosureToPHP;

    public const string NAME = 'vo_money';

    #[Override]
    public function convertToDatabaseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Money) {
            throw new InvalidArgumentException(\sprintf('Expected %s, got %s.', Money::class, \get_debug_type($value)));
        }

        return $value->getCurrency()->getCurrencyCode() . ' ' . $value->getAmount();
    }

    #[Override]
    public function convertToPHPValue(mixed $value): ?Money
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string database value, got %s.', \get_debug_type($value)));
        }

        $parts = \explode(' ', $value, 2);
        if (\count($parts) !== 2) {
            throw new InvalidArgumentException(\sprintf('Expected a "CURRENCY amount" database value, got "%s".', $value));
        }

        [$currency, $amount] = $parts;

        return Money::of($amount, $currency);
    }
}
