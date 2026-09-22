<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm\Embeddable;

use Brick\Money\Money;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyContextRouter;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyMappingException;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyScale;

/**
 * Two-column money: the amount in the currency's minor units, plus the currency.
 *
 * Unlike the single-column vo_money type, this shape lets the database do the
 * aggregation -- but only within one currency. The scale is derived from the
 * currency, so JPY 1234 and USD 1.23 sit in the field as 1234 and 123; summing
 * across currencies produces a plausible, meaningless number. Group or match on the
 * currency field before aggregating.
 *
 * The field cannot be nullable. Doctrine instantiates an embedded document
 * without calling the constructor and, for an absent sub-document, leaves the
 * properties uninitialised rather than producing null -- no exception at
 * hydration, just an object that fails on first access. Use vo_money or two
 * plain fields when the amount is optional.
 *
 * brick/money is needed only by the two conversion methods; storage, comparison
 * and the decimal form work without it.
 */
#[ODM\EmbeddedDocument]
final readonly class MoneyAmount
{
    public function __construct(
        #[ODM\Field(type: 'int64')]
        public int $minorUnits,
        #[ODM\Field(type: 'string', enumType: CurrencyCode::class)]
        public CurrencyCode $currency,
    ) {
        // The int64 bound is not checked here: a PHP int is already within it on
        // a 64-bit build. Overflow is only reachable through fromBrickMoney.
        MoneyScale::assertIsoKnown($currency);
    }

    /**
     * @throws MoneyMappingException when the context is not the currency's default scale,
     *                               the amount exceeds int64, or the currency has no ISO minor units
     */
    public static function fromBrickMoney(Money $money): self
    {
        MoneyContextRouter::assertBaseContext($money);

        $minor = $money->getMinorAmount();
        MoneyScale::assertFitsInt64((string) $minor);

        return new self($minor->toInt(), CurrencyCode::from($money->getCurrency()->getCurrencyCode()));
    }

    public function toBrickMoney(): Money
    {
        return Money::ofMinor($this->minorUnits, $this->currency->value);
    }

    /**
     * The decimal form, without brick/money. Uses string arithmetic so that
     * PHP_INT_MIN is handled like any other value.
     */
    public function toDecimalString(): string
    {
        $scale = MoneyScale::base($this->currency);
        $digits = \ltrim((string) $this->minorUnits, '-');
        $sign = $this->minorUnits < 0 ? '-' : '';

        if ($scale === 0) {
            return $sign . $digits;
        }

        $digits = \str_pad($digits, $scale + 1, '0', \STR_PAD_LEFT);

        return $sign . \substr($digits, 0, -$scale) . '.' . \substr($digits, -$scale);
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }
}
