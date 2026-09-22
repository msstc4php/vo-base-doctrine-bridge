<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Odm;

use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\EmailAddressType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\GeoCoordinatesType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\PortType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\TypeRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailAddressType::class)]
#[CoversClass(PortType::class)]
#[CoversClass(GeoCoordinatesType::class)]
#[CoversClass(TypeRegistrar::class)]
final class TypeRoundTripTest extends TestCase
{
    protected function setUp(): void
    {
        TypeRegistrar::registerAll();
    }

    /**
     * @return iterable<string, array{string, ValueObject}>
     */
    public static function stringValueObjects(): iterable
    {
        yield 'email' => [EmailAddressType::NAME, EmailAddress::from('user@example.com')];
        yield 'uuid' => ['vo_uuid', Uuid::from('018f8c1e-7b2a-7cde-8f01-2a3b4c5d6e7f')];
    }

    #[DataProvider('stringValueObjects')]
    public function testStringValueObjectRoundTrips(string $typeName, ValueObject $vo): void
    {
        $type = Type::getType($typeName);

        $db = $type->convertToDatabaseValue($vo);
        self::assertIsString($db);

        $restored = $type->convertToPHPValue($db);
        self::assertInstanceOf(ValueObject::class, $restored);
        self::assertTrue($vo->equals($restored));
    }

    public function testNullRoundTrips(): void
    {
        $type = Type::getType(EmailAddressType::NAME);

        self::assertNull($type->convertToDatabaseValue(null));
        self::assertNull($type->convertToPHPValue(null));
    }

    public function testWrongTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(EmailAddressType::NAME)->convertToDatabaseValue('not-a-vo');
    }

    public function testPortRoundTripsAsInteger(): void
    {
        $type = Type::getType(PortType::NAME);

        $db = $type->convertToDatabaseValue(Port::from(8080));
        self::assertSame(8080, $db);

        $restored = $type->convertToPHPValue($db);
        self::assertInstanceOf(Port::class, $restored);
        self::assertTrue(Port::from(8080)->equals($restored));
    }

    public function testGeoCoordinatesRoundTripsAsArray(): void
    {
        $type = Type::getType(GeoCoordinatesType::NAME);
        $coordinates = GeoCoordinates::fromFloats(51.5074, -0.1278);

        $db = $type->convertToDatabaseValue($coordinates);
        self::assertSame(['lat' => 51.5074, 'lng' => -0.1278], $db);

        $restored = $type->convertToPHPValue($db);
        self::assertInstanceOf(GeoCoordinates::class, $restored);
        self::assertTrue($coordinates->equals($restored));
    }

    public function testGeoCoordinatesWrongTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(GeoCoordinatesType::NAME)->convertToPHPValue('not-an-array');
    }
}
