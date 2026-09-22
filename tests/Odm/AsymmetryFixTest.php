<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Odm;

use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\BrickMoneyType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\DateRangeType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\GeoCoordinatesType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\TypeRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BrickMoneyType::class)]
#[CoversClass(DateRangeType::class)]
#[CoversClass(GeoCoordinatesType::class)]
final class AsymmetryFixTest extends TestCase
{
    protected function setUp(): void
    {
        TypeRegistrar::registerAll();
    }

    /**
     * @return iterable<string, array{Money}>
     */
    public static function moneyProvider(): iterable
    {
        yield 'default USD' => [Money::of('1.23', 'USD')];
        yield 'default JPY' => [Money::of('1234', 'JPY')];
        yield 'custom scale 4' => [Money::of('1.2345', 'USD', new CustomContext(4))];
        yield 'trailing zeros' => [Money::of('1.2300', 'USD', new CustomContext(4))];
        yield 'negative' => [Money::of('-0.05', 'USD')];
    }

    #[DataProvider('moneyProvider')]
    public function testMoneyRoundTripKeepsValueAndScale(Money $money): void
    {
        $type = Type::getType(BrickMoneyType::NAME);

        $back = $type->convertToPHPValue($type->convertToDatabaseValue($money));

        self::assertInstanceOf(Money::class, $back);
        self::assertTrue($money->isEqualTo($back));
        self::assertSame($money->getAmount()->getScale(), $back->getAmount()->getScale());
    }

    public function testMoneyWrittenBeforeTheFixStillReads(): void
    {
        $back = Type::getType(BrickMoneyType::NAME)->convertToPHPValue('USD 1.23');

        self::assertInstanceOf(Money::class, $back);
        self::assertInstanceOf(DefaultContext::class, $back->getContext());
    }

    public function testUnknownCurrencyIsNotSwallowed(): void
    {
        $this->expectException(UnknownCurrencyException::class);

        Type::getType(BrickMoneyType::NAME)->convertToPHPValue('ADP 1.23');
    }

    /**
     * @return iterable<string, array{DateRange}>
     */
    public static function dateRangeProvider(): iterable
    {
        $ny = new DateTimeZone('America/New_York');
        $utc = new DateTimeZone('UTC');

        yield 'microseconds and named zone' => [new DateRange(
            new DateTimeImmutable('2026-08-22 10:00:00.123456', new DateTimeZone('Europe/Moscow')),
            new DateTimeImmutable('2026-08-23 10:00:00.654321', new DateTimeZone('Europe/Moscow')),
        )];
        yield 'first 01:30 of the fall-back' => [new DateRange(
            new DateTimeImmutable('2026-11-01 05:30:00.123456', $utc)->setTimezone($ny),
            new DateTimeImmutable('2026-11-02 05:30:00.000000', $utc)->setTimezone($ny),
        )];
        yield 'second 01:30 of the fall-back' => [new DateRange(
            new DateTimeImmutable('2026-11-01 06:30:00.123456', $utc)->setTimezone($ny),
            new DateTimeImmutable('2026-11-02 06:30:00.000000', $utc)->setTimezone($ny),
        )];
        yield 'canonical whole seconds in UTC' => [new DateRange(
            new DateTimeImmutable('2026-01-01 00:00:00', $utc),
            new DateTimeImmutable('2026-12-31 23:59:59', $utc),
        )];
    }

    #[DataProvider('dateRangeProvider')]
    public function testDateRangeRoundTripIsIdentity(DateRange $range): void
    {
        $type = Type::getType(DateRangeType::NAME);

        $back = $type->convertToPHPValue($type->convertToDatabaseValue($range));

        self::assertInstanceOf(DateRange::class, $back);
        self::assertTrue($range->equals($back));
        self::assertSame($range->start->getTimestamp(), $back->start->getTimestamp());
        self::assertSame($range->start->format('u'), $back->start->format('u'));
    }

    public function testDateRangeRejectsSubDocumentWithoutEnd(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(DateRangeType::NAME)->convertToPHPValue(['start' => '2026-01-01T00:00:00.000000+00:00']);
    }

    public function testGeoCoordinatesIsUnaffected(): void
    {
        $type = Type::getType(GeoCoordinatesType::NAME);
        $coordinates = new GeoCoordinates(Latitude::from(-89.999999), Longitude::from(179.999999));

        $back = $type->convertToPHPValue($type->convertToDatabaseValue($coordinates));

        self::assertInstanceOf(GeoCoordinates::class, $back);
        self::assertTrue($coordinates->equals($back));
    }
}
