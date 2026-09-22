<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Orm\Embeddable;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\Embeddable\MoneyAmount;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\Embeddable\PreciseMoneyAmount;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyMappingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PreciseMoneyAmount::class)]
final class PreciseMoneyAmountTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public static function roundTripProvider(): iterable
    {
        // The direct regression on Money::ofMinor, which would return USD 123.4500.
        yield 'USD at scale 4' => ['1.2345', 'USD', 4, 12_345];
        yield 'JPY at scale 2' => ['1234.56', 'JPY', 2, 123_456];
        yield 'IQD at scale 5' => ['1.23456', 'IQD', 5, 123_456];
        // The largest scale the "+2" rule produces in the current register, and
        // the only one above the ISO 20022 ceiling of five.
        yield 'CLF at scale 6' => ['1.234567', 'CLF', 6, 1_234_567];
        yield 'negative USD' => ['-1.2345', 'USD', 4, -12_345];
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTripIsIdentity(string $amount, string $code, int $scale, int $expectedUnscaled): void
    {
        $money = Money::of($amount, $code, new CustomContext($scale));
        $precise = PreciseMoneyAmount::fromBrickMoney($money);

        self::assertSame($expectedUnscaled, $precise->unscaledValue);
        self::assertTrue($money->isEqualTo($precise->toBrickMoney()));
        self::assertSame($scale, $precise->toBrickMoney()->getAmount()->getScale());
    }

    public function testWideningFromDefaultContextIsSilent(): void
    {
        $precise = PreciseMoneyAmount::fromBrickMoney(Money::of('1.23', 'USD'));

        self::assertSame(12_300, $precise->unscaledValue);
        self::assertSame('1.2300', (string) $precise->toBrickMoney()->getAmount());
    }

    public function testScaleAboveTargetIsRefused(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/narrowing loses money/');

        PreciseMoneyAmount::fromBrickMoney(Money::of('1.234567', 'USD', new CustomContext(6)));
    }

    public function testCashContextIsRefused(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/vo_money/');

        PreciseMoneyAmount::fromBrickMoney(Money::of('1.25', 'CHF', new CashContext(5)));
    }

    public function testWideningFromMoneyAmountIsSilent(): void
    {
        $precise = PreciseMoneyAmount::fromMoneyAmount(new MoneyAmount(123, CurrencyCode::USD));

        self::assertSame(12_300, $precise->unscaledValue);
        self::assertSame(CurrencyCode::USD, $precise->currency);
    }

    /**
     * @return iterable<string, array{int, RoundingMode, int}>
     */
    public static function narrowingProvider(): iterable
    {
        yield 'exact, no rounding needed' => [12_300, RoundingMode::Unnecessary, 123];
        // 124.50 minor units: half-even goes to the even 124, half-up to 125.
        yield 'half-even at a tie' => [12_450, RoundingMode::HalfEven, 124];
        yield 'half-up at the same tie' => [12_450, RoundingMode::HalfUp, 125];
        yield 'negative half-even' => [-12_450, RoundingMode::HalfEven, -124];
        yield 'down' => [12_399, RoundingMode::Down, 123];
    }

    #[DataProvider('narrowingProvider')]
    public function testNarrowingUsesTheGivenRoundingMode(int $unscaled, RoundingMode $mode, int $expectedMinor): void
    {
        $narrowed = new PreciseMoneyAmount($unscaled, CurrencyCode::USD)->toMoneyAmount($mode);

        self::assertSame($expectedMinor, $narrowed->minorUnits);
    }

    /**
     * There is no default rounding mode, and Unnecessary genuinely refuses --
     * narrowing must never silently drop money.
     */
    public function testNarrowingRefusesWhenRoundingWouldBeNeeded(): void
    {
        $this->expectException(RoundingNecessaryException::class);

        new PreciseMoneyAmount(12_350, CurrencyCode::USD)->toMoneyAmount(RoundingMode::Unnecessary);
    }

    /**
     * @return iterable<string, array{int, CurrencyCode, string}>
     */
    public static function decimalStringProvider(): iterable
    {
        yield 'USD' => [12_345, CurrencyCode::USD, '1.2345'];
        yield 'JPY' => [123_456, CurrencyCode::JPY, '1234.56'];
        yield 'CLF' => [1_234_567, CurrencyCode::CLF, '1.234567'];
        yield 'negative sub-unit' => [-5, CurrencyCode::USD, '-0.0005'];
        yield 'zero' => [0, CurrencyCode::USD, '0.0000'];
    }

    #[DataProvider('decimalStringProvider')]
    public function testDecimalStringNeedsNoMoneyLibrary(int $unscaled, CurrencyCode $currency, string $expected): void
    {
        self::assertSame($expected, new PreciseMoneyAmount($unscaled, $currency)->toDecimalString());
    }

    public function testHistoricCurrencyIsRejected(): void
    {
        $this->expectException(MoneyMappingException::class);

        new PreciseMoneyAmount(100, CurrencyCode::ADP);
    }

    public function testWideningBeyondInt64IsRejected(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/64-bit/');

        PreciseMoneyAmount::fromMoneyAmount(new MoneyAmount(\PHP_INT_MAX, CurrencyCode::USD));
    }

    public function testEqualsDistinguishesCurrency(): void
    {
        $usd = new PreciseMoneyAmount(12_345, CurrencyCode::USD);

        self::assertTrue($usd->equals(new PreciseMoneyAmount(12_345, CurrencyCode::USD)));
        self::assertFalse($usd->equals(new PreciseMoneyAmount(12_345, CurrencyCode::EUR)));
    }
}
