<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Brick\Money\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Override;

/**
 * Maps a {@see Money} amount to a single VARCHAR column in the compact
 * "CURRENCY amount" form (e.g. "USD 100.00"). When the amount must be
 * aggregated in SQL, store the minor-unit integer and currency in two columns
 * instead of using this type.
 */
final class BrickMoneyType extends Type
{
    public const string NAME = 'vo_money';

    private const int COLUMN_LENGTH = 40;

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => self::COLUMN_LENGTH] + $column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
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
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Expected a string column value, got %s.', \get_debug_type($value)));
        }

        $parts = \explode(' ', $value, 2);
        if (\count($parts) !== 2) {
            throw new InvalidArgumentException(\sprintf('Expected a "CURRENCY amount" column value, got "%s".', $value));
        }

        [$currency, $amount] = $parts;

        return Money::of($amount, $currency);
    }
}
