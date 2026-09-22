<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Shared\Money;

use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyContextRouter;
use MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money\MoneyMappingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoneyContextRouter::class)]
final class MoneyContextRouterTest extends TestCase
{
    /**
     * @return iterable<string, array{Money}>
     */
    public static function defaultContextProvider(): iterable
    {
        yield 'USD' => [Money::of('1.23', 'USD')];
        yield 'JPY' => [Money::of('1234', 'JPY')];
        yield 'IQD' => [Money::of('1.234', 'IQD')];
    }

    #[DataProvider('defaultContextProvider')]
    public function testBaseContextAccepted(Money $money): void
    {
        MoneyContextRouter::assertBaseContext($money);

        $this->expectNotToPerformAssertions();
    }

    public function testBaseContextRejectsCustomContextAndNamesTheAlternative(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/high-precision embeddable/');

        MoneyContextRouter::assertBaseContext(Money::of('1.2345', 'USD', new CustomContext(4)));
    }

    public function testBaseContextRejectsCashContext(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/CashContext.*vo_money/s');

        MoneyContextRouter::assertBaseContext(Money::of('1.25', 'CHF', new CashContext(5)));
    }

    // AutoContext calls the deprecated BigDecimal::stripTrailingZeros() upstream;
    // that is one of the reasons it is refused, and it is recorded in known-issues.
    #[IgnoreDeprecations]
    public function testBaseContextRejectsAutoContext(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/AutoContext.*vo_money/s');

        MoneyContextRouter::assertBaseContext(Money::of('1.23', 'USD', new AutoContext()));
    }

    /**
     * The refusal must warn that vo_money does not preserve the context either,
     * otherwise it steers the caller into the same write/read asymmetry the
     * BrickMoneyType fix exists to remove.
     */
    public function testUnmappableContextMessageWarnsThatVoMoneyLosesContext(): void
    {
        try {
            MoneyContextRouter::assertBaseContext(Money::of('1.25', 'CHF', new CashContext(5)));
            self::fail('Expected a MoneyMappingException.');
        } catch (MoneyMappingException $e) {
            self::assertStringContainsString('does not preserve the context', $e->getMessage());
            self::assertStringContainsString('rounding step', $e->getMessage());
        }
    }

    public function testPreciseContextAcceptsExactTargetScale(): void
    {
        MoneyContextRouter::assertPreciseContext(Money::of('1.2345', 'USD', new CustomContext(4)), 4);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return iterable<string, array{Money, int<0, max>}>
     */
    public static function wideningProvider(): iterable
    {
        yield 'USD default into precise' => [Money::of('1.23', 'USD'), 4];
        yield 'JPY default into precise' => [Money::of('1234', 'JPY'), 2];
        yield 'USD custom below target' => [Money::of('1.2', 'USD', new CustomContext(1)), 4];
    }

    /**
     * @param int<0, max> $targetScale
     */
    #[DataProvider('wideningProvider')]
    public function testPreciseContextAcceptsWidening(Money $money, int $targetScale): void
    {
        MoneyContextRouter::assertPreciseContext($money, $targetScale);

        $this->expectNotToPerformAssertions();
    }

    public function testPreciseContextRejectsScaleAboveTarget(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/narrowing loses money/');

        MoneyContextRouter::assertPreciseContext(Money::of('1.234567', 'USD', new CustomContext(6)), 4);
    }

    public function testPreciseContextRejectsCashContext(): void
    {
        $this->expectException(MoneyMappingException::class);
        $this->expectExceptionMessageMatches('/vo_money/');

        MoneyContextRouter::assertPreciseContext(Money::of('1.25', 'CHF', new CashContext(5)), 4);
    }
}
