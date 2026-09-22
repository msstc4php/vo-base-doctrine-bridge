<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Contract;

use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\Embeddable\MoneyAmount as OdmMoneyAmount;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\Embeddable\PreciseMoneyAmount as OdmPrecise;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\Embeddable\MoneyAmount as OrmMoneyAmount;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\Embeddable\PreciseMoneyAmount as OrmPrecise;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ODM stores money as int64 rather than Decimal128 so that both branches keep
 * one PHP API. That trade is only worth anything if the two really do behave
 * identically, so it is checked rather than assumed.
 */
#[CoversNothing]
final class MoneyBranchParityTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function amountProvider(): iterable
    {
        yield 'USD' => ['1.23', 'USD'];
        yield 'JPY' => ['1234', 'JPY'];
        yield 'IQD' => ['1.234', 'IQD'];
        yield 'HUF' => ['1000', 'HUF'];
        yield 'negative' => ['-0.05', 'USD'];
    }

    #[DataProvider('amountProvider')]
    public function testBaseMappingAgreesAcrossBranches(string $amount, string $code): void
    {
        $money = Money::of($amount, $code);

        $orm = OrmMoneyAmount::fromBrickMoney($money);
        $odm = OdmMoneyAmount::fromBrickMoney($money);

        self::assertSame($orm->minorUnits, $odm->minorUnits);
        self::assertSame($orm->currency, $odm->currency);
        self::assertSame($orm->toDecimalString(), $odm->toDecimalString());
        self::assertTrue($orm->toBrickMoney()->isEqualTo($odm->toBrickMoney()));
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function preciseProvider(): iterable
    {
        yield 'USD' => ['1.2345', 'USD', 4];
        yield 'JPY' => ['1234.56', 'JPY', 2];
        yield 'CLF' => ['1.234567', 'CLF', 6];
    }

    #[DataProvider('preciseProvider')]
    public function testPreciseMappingAgreesAcrossBranches(string $amount, string $code, int $scale): void
    {
        $money = Money::of($amount, $code, new CustomContext($scale));

        $orm = OrmPrecise::fromBrickMoney($money);
        $odm = OdmPrecise::fromBrickMoney($money);

        self::assertSame($orm->unscaledValue, $odm->unscaledValue);
        self::assertSame($orm->toDecimalString(), $odm->toDecimalString());
        self::assertTrue($orm->toBrickMoney()->isEqualTo($odm->toBrickMoney()));
    }

    public function testNarrowingAgreesAcrossBranches(): void
    {
        $orm = new OrmPrecise(12_450, CurrencyCode::USD)->toMoneyAmount(RoundingMode::HalfEven);
        $odm = new OdmPrecise(12_450, CurrencyCode::USD)->toMoneyAmount(RoundingMode::HalfEven);

        self::assertSame($orm->minorUnits, $odm->minorUnits);
    }
}
