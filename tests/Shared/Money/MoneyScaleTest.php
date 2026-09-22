<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Shared\Money;

use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyMappingException;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyScale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoneyScale::class)]
#[CoversClass(MoneyMappingException::class)]
final class MoneyScaleTest extends TestCase
{
    /**
     * @return iterable<string, array{CurrencyCode, int, int}>
     */
    public static function scaleProvider(): iterable
    {
        yield 'USD' => [CurrencyCode::USD, 2, 4];
        yield 'JPY' => [CurrencyCode::JPY, 0, 2];
        yield 'IQD' => [CurrencyCode::IQD, 3, 5];
        // CLF and UYW are the only four-digit currencies in the register, so the
        // high-precision rule yields 6 here -- above the ISO 20022 generic-amount
        // ceiling of 5. Accepted deliberately; pinned so it stays visible.
        yield 'CLF' => [CurrencyCode::CLF, 4, 6];
        yield 'UYW' => [CurrencyCode::UYW, 4, 6];
    }

    #[DataProvider('scaleProvider')]
    public function testBaseAndPreciseScales(CurrencyCode $currency, int $base, int $precise): void
    {
        self::assertSame($base, MoneyScale::base($currency));
        self::assertSame($precise, MoneyScale::precise($currency));
    }

    /**
     * @return iterable<string, array{CurrencyCode}>
     */
    public static function historicCurrencyProvider(): iterable
    {
        yield 'ADP' => [CurrencyCode::ADP];
        yield 'BEF' => [CurrencyCode::BEF];
    }

    #[DataProvider('historicCurrencyProvider')]
    public function testHistoricCurrencyIsRejected(CurrencyCode $currency): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/"' . $currency->value . '"/');

        MoneyScale::base($currency);
    }

    #[DataProvider('historicCurrencyProvider')]
    public function testAssertIsoKnownRejectsHistoricCurrency(CurrencyCode $currency): void
    {
        $this->expectException(MoneyMappingException::class);

        MoneyScale::assertIsoKnown($currency);
    }

    public function testAssertIsoKnownAcceptsSupportedCurrency(): void
    {
        MoneyScale::assertIsoKnown(CurrencyCode::USD);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function withinInt64Provider(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative zero' => ['-0'];
        yield 'small positive' => ['123'];
        yield 'small negative' => ['-5'];
        yield 'int max' => [(string) \PHP_INT_MAX];
        yield 'int min' => [(string) \PHP_INT_MIN];
        yield 'zero fraction is integral' => ['12345.00'];
        yield 'leading zeros' => ['0000123'];
    }

    #[DataProvider('withinInt64Provider')]
    public function testAcceptsIntegralValuesInRange(string $value): void
    {
        MoneyScale::assertFitsInt64($value);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outOfRangeProvider(): iterable
    {
        yield 'one past int max' => ['9223372036854775808'];
        yield 'one past int min' => ['-9223372036854775809'];
        yield 'far past range' => ['99999999999999999999999999'];
    }

    #[DataProvider('outOfRangeProvider')]
    public function testRejectsValuesOutsideInt64(string $value): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/64-bit/');

        MoneyScale::assertFitsInt64($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fractionalProvider(): iterable
    {
        yield 'non-zero fraction' => ['123.45'];
        yield 'tiny fraction' => ['1.0000001'];
        yield 'negative fraction' => ['-0.5'];
    }

    #[DataProvider('fractionalProvider')]
    public function testRejectsFractionalAmounts(string $value): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/integral/');

        MoneyScale::assertFitsInt64($value);
    }
}
