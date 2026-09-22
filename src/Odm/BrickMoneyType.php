<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use Brick\Math\BigDecimal;
use Brick\Money\Context;
use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Money;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
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

        return Money::of($amount, $currency, $this->contextFor($currency, $amount));
    }

    /**
     * Writing hands over the full BigDecimal, so the stored value may carry a
     * scale larger than the currency's. Reading with the implicit DefaultContext
     * would then throw RoundingNecessaryException -- the type would be unable to
     * read what it just wrote. The scale is therefore taken back from the value.
     *
     * DefaultContext is kept whenever the scale matches the currency's ISO minor
     * units, so ordinary amounts round-trip with their context identity intact.
     *
     * AutoContext is not an option: it strips trailing zeros, so "USD 1.2300"
     * would come back at scale 2, and it calls a method deprecated in brick/math.
     */
    private function contextFor(string $currency, string $amount): Context
    {
        $scale = BigDecimal::of($amount)->getScale();
        $isoScale = CurrencyCode::tryFrom($currency)?->isoMinorUnits();

        return $scale === $isoScale ? new DefaultContext() : new CustomContext($scale);
    }
}
