<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Odm\Embeddable;

use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\Embeddable\MoneyAmount;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyMappingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoneyAmount::class)]
final class MoneyAmountTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function roundTripProvider(): iterable
    {
        yield 'USD' => ['1.23', 'USD', 123];
        yield 'JPY' => ['1234', 'JPY', 1234];
        yield 'IQD' => ['1.234', 'IQD', 1234];
        // Where CLDR display digits and ISO minor units disagree. Reading these
        // through the display value would divide by 10^0 and return 1000.00 HUF
        // as 10.00 -- the defect the single-source rule exists to prevent.
        yield 'HUF' => ['1000', 'HUF', 100_000];
        yield 'IDR' => ['5000', 'IDR', 500_000];
        yield 'COP' => ['200', 'COP', 20_000];
        yield 'negative USD' => ['-0.05', 'USD', -5];
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTripThroughBrickMoney(string $amount, string $code, int $expectedMinor): void
    {
        $money = Money::of($amount, $code);
        $embeddable = MoneyAmount::fromBrickMoney($money);

        self::assertSame($expectedMinor, $embeddable->minorUnits);
        self::assertSame($code, $embeddable->currency->value);
        self::assertTrue($money->isEqualTo($embeddable->toBrickMoney()));
    }

    /**
     * @return iterable<string, array{int, CurrencyCode, string}>
     */
    public static function decimalStringProvider(): iterable
    {
        yield 'USD' => [123, CurrencyCode::USD, '1.23'];
        yield 'JPY' => [1234, CurrencyCode::JPY, '1234'];
        yield 'IQD' => [1234, CurrencyCode::IQD, '1.234'];
        yield 'sub-unit USD' => [5, CurrencyCode::USD, '0.05'];
        // The shapes a naive implementation gets wrong: "-0.5" or "0.-5".
        yield 'negative sub-unit USD' => [-5, CurrencyCode::USD, '-0.05'];
        yield 'negative JPY' => [-1234, CurrencyCode::JPY, '-1234'];
        yield 'zero' => [0, CurrencyCode::USD, '0.00'];
        yield 'int min' => [\PHP_INT_MIN, CurrencyCode::USD, '-92233720368547758.08'];
    }

    #[DataProvider('decimalStringProvider')]
    public function testDecimalStringNeedsNoMoneyLibrary(int $minorUnits, CurrencyCode $currency, string $expected): void
    {
        self::assertSame($expected, new MoneyAmount($minorUnits, $currency)->toDecimalString());
    }

    public function testHistoricCurrencyIsRejected(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/"ADP"/');

        new MoneyAmount(100, CurrencyCode::ADP);
    }

    public function testCustomContextIsRoutedToThePreciseMapping(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/high-precision embeddable/');

        MoneyAmount::fromBrickMoney(Money::of('1.2345', 'USD', new CustomContext(4)));
    }

    public function testCashContextIsRejected(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/vo_money/');

        MoneyAmount::fromBrickMoney(Money::of('1.25', 'CHF', new CashContext(5)));
    }

    #[IgnoreDeprecations]
    public function testAutoContextIsRejected(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/vo_money/');

        MoneyAmount::fromBrickMoney(Money::of('1.23', 'USD', new AutoContext()));
    }

    /**
     * The guard has to run on the full-precision value before toInt(), or the
     * caller gets brick/math's own overflow exception instead of a domain error.
     */
    public function testAmountBeyondInt64IsRejectedWithADomainError(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/64-bit/');

        MoneyAmount::fromBrickMoney(Money::of('99999999999999999999', 'USD'));
    }

    public function testEqualsDistinguishesCurrency(): void
    {
        $usd = new MoneyAmount(123, CurrencyCode::USD);

        self::assertTrue($usd->equals(new MoneyAmount(123, CurrencyCode::USD)));
        self::assertFalse($usd->equals(new MoneyAmount(123, CurrencyCode::EUR)));
        self::assertFalse($usd->equals(new MoneyAmount(124, CurrencyCode::USD)));
    }
}
