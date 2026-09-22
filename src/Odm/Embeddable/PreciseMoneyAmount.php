<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm\Embeddable;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyContextRouter;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyMappingException;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyScale;

/**
 * Money at two digits beyond the currency's minor unit, for intermediate
 * results and sub-cent prices.
 *
 * Not a parameter of {@see MoneyAmount} but a separate class, because the
 * storage form differs: the column holds the unscaled value, not minor units,
 * and reconstruction must not go through Money::ofMinor(). That method divides
 * by 10^isoMinorUnits regardless of the context's scale, so ofMinor(12345,
 * 'USD', CustomContext(4)) yields USD 123.4500 rather than USD 1.2345.
 *
 * "Two beyond the minor unit" is this project's rule, not a standard. The
 * dominant industry convention is a fixed four decimals (DECIMAL(19,4)); for
 * USD and EUR the two coincide. For CLF and UYW, the only four-digit currencies
 * in the register, the rule yields scale 6 -- above the ISO 20022 generic-amount
 * ceiling of five. Accepted deliberately.
 *
 * As with MoneyAmount, the field cannot be nullable.
 */
#[ODM\EmbeddedDocument]
final readonly class PreciseMoneyAmount
{
    public function __construct(
        #[ODM\Field(type: 'int64')]
        public int $unscaledValue,
        #[ODM\Field(type: 'string', enumType: CurrencyCode::class)]
        public CurrencyCode $currency,
    ) {
        MoneyScale::assertIsoKnown($currency);
    }

    /**
     * Widening is lossless and happens silently; a scale above the target is
     * refused, because narrowing loses money.
     *
     * @throws MoneyMappingException when the scale exceeds the target, the context is
     *                               unmappable, or the value exceeds int64
     */
    public static function fromBrickMoney(Money $money): self
    {
        $currency = CurrencyCode::from($money->getCurrency()->getCurrencyCode());
        $scale = MoneyScale::precise($currency);

        MoneyContextRouter::assertPreciseContext($money, $scale);

        $unscaled = $money->getAmount()->toScale($scale)->getUnscaledValue();
        MoneyScale::assertFitsInt64((string) $unscaled);

        return new self($unscaled->toInt(), $currency);
    }

    public function toBrickMoney(): Money
    {
        $scale = MoneyScale::precise($this->currency);

        return Money::of(
            BigDecimal::ofUnscaledValue($this->unscaledValue, $scale),
            $this->currency->value,
            new CustomContext($scale),
        );
    }

    /**
     * @throws MoneyMappingException when the widened value exceeds int64
     */
    public static function fromMoneyAmount(MoneyAmount $amount): self
    {
        $unscaled = BigInteger::of($amount->minorUnits)->multipliedBy(10 ** MoneyScale::PRECISION_SURPLUS);
        MoneyScale::assertFitsInt64((string) $unscaled);

        return new self($unscaled->toInt(), $amount->currency);
    }

    /**
     * Narrowing drops the surplus digits, so the rounding mode is a required
     * argument with no default. RoundingMode::Unnecessary makes the call fail
     * rather than round when the surplus digits are not zero.
     */
    public function toMoneyAmount(RoundingMode $roundingMode): MoneyAmount
    {
        $minor = BigDecimal::ofUnscaledValue($this->unscaledValue, MoneyScale::PRECISION_SURPLUS)
            ->toScale(0, $roundingMode)
            ->getUnscaledValue()
        ;

        MoneyScale::assertFitsInt64((string) $minor);

        return new MoneyAmount($minor->toInt(), $this->currency);
    }

    /**
     * The decimal form, without brick/money. String arithmetic, so PHP_INT_MIN
     * behaves like any other value.
     */
    public function toDecimalString(): string
    {
        $scale = MoneyScale::precise($this->currency);
        $digits = \str_pad(\ltrim((string) $this->unscaledValue, '-'), $scale + 1, '0', \STR_PAD_LEFT);
        $sign = $this->unscaledValue < 0 ? '-' : '';

        return $sign . \substr($digits, 0, -$scale) . '.' . \substr($digits, -$scale);
    }

    public function equals(self $other): bool
    {
        return $this->unscaledValue === $other->unscaledValue && $this->currency === $other->currency;
    }
}
