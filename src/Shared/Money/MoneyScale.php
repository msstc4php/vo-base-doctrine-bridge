<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money;

use MSSTC4PHP\ValueObject\I18n\CurrencyCode;

/**
 * Scale rules shared by every money embeddable, in both branches.
 *
 * The only runtime source of ISO 4217 minor units is the core enum. Reaching
 * for brick/money's `Currency::getDefaultFractionDigits()` here would give the
 * process two tables of the same fact, and a version skew between them shifts
 * stored amounts by orders of magnitude. The contract test in tests/Contract
 * guards the two against drift.
 *
 * Deliberately free of brick/math and brick/money: scale arithmetic, the
 * integrality check and the range check all work on numeric strings.
 */
final class MoneyScale
{
    /** Extra digits the high-precision mapping carries beyond the minor unit. */
    public const int PRECISION_SURPLUS = 2;

    private function __construct()
    {
    }

    /**
     * @return int<0, max>
     *
     * @throws MoneyMappingException when the currency has no ISO 4217 minor-unit value
     */
    public static function base(CurrencyCode $currency): int
    {
        $scale = $currency->isoMinorUnits();

        if ($scale === null) {
            throw new MoneyMappingException(\sprintf('Currency "%s" has no ISO 4217 minor-unit value and cannot be mapped as money; it is outside the current register, most likely a historic code.', $currency->value));
        }

        return $scale;
    }

    /**
     * @return int<0, max>
     *
     * @throws MoneyMappingException when the currency has no ISO 4217 minor-unit value
     */
    public static function precise(CurrencyCode $currency): int
    {
        return self::base($currency) + self::PRECISION_SURPLUS;
    }

    /**
     * @throws MoneyMappingException when the currency has no ISO 4217 minor-unit value
     */
    public static function assertIsoKnown(CurrencyCode $currency): void
    {
        self::base($currency);
    }

    /**
     * Rejects anything that will not survive as a signed 64-bit integer, before
     * a conversion such as `BigDecimal::toInt()` gets a chance to throw its own
     * library exception at the caller.
     *
     * Takes the raw decimal string as produced by brick/math's __toString; it is
     * this method's job to reject anything that is not an integral amount, so no
     * pre-validated type is required of the caller.
     *
     * @throws MoneyMappingException when the value is not integral or is out of int64 range
     */
    public static function assertFitsInt64(string $value): void
    {
        $integral = self::stripZeroFraction($value);

        if ($integral === null) {
            throw new MoneyMappingException(\sprintf('Expected an integral amount of minor units, got "%s".', $value));
        }

        if (!self::withinInt64($integral)) {
            throw new MoneyMappingException(\sprintf('Amount "%s" does not fit a signed 64-bit integer column (max %d).', $value, \PHP_INT_MAX));
        }
    }

    /**
     * @return string|null the integral form, or null when the value is not an integer
     */
    private static function stripZeroFraction(string $value): ?string
    {
        if (\preg_match('/^-?\d+$/', $value) === 1) {
            return $value;
        }

        if (\preg_match('/^(-?\d+)\.0+$/', $value, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private static function withinInt64(string $integral): bool
    {
        $negative = \str_starts_with($integral, '-');
        $digits = \ltrim($negative ? \substr($integral, 1) : $integral, '0');

        if ($digits === '') {
            return true;
        }

        $limit = $negative ? (string) \PHP_INT_MIN : (string) \PHP_INT_MAX;
        $limitDigits = \ltrim($negative ? \substr($limit, 1) : $limit, '0');

        if (\strlen($digits) !== \strlen($limitDigits)) {
            return \strlen($digits) < \strlen($limitDigits);
        }

        return \strcmp($digits, $limitDigits) <= 0;
    }
}
