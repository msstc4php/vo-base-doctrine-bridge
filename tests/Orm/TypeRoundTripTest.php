<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Orm;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\EmailAddressType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\GeoCoordinatesType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\PortType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\TypeRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailAddressType::class)]
#[CoversClass(PortType::class)]
#[CoversClass(GeoCoordinatesType::class)]
#[CoversClass(TypeRegistrar::class)]
final class TypeRoundTripTest extends TestCase
{
    private SQLitePlatform $platform;

    protected function setUp(): void
    {
        TypeRegistrar::registerAll();
        $this->platform = new SQLitePlatform();
    }

    /**
     * @return iterable<string, array{string, ValueObject}>
     */
    public static function stringValueObjects(): iterable
    {
        yield 'email' => [EmailAddressType::NAME, EmailAddress::from('user@example.com')];
        yield 'uuid' => ['vo_uuid', Uuid::from('018f8c1e-7b2a-7cde-8f01-2a3b4c5d6e7f')];
        yield 'ip' => ['vo_ip_address', IpAddress::fromString('2001:db8::1')];
    }

    #[DataProvider('stringValueObjects')]
    public function testStringValueObjectRoundTrips(string $typeName, ValueObject $vo): void
    {
        $type = Type::getType($typeName);

        $db = $type->convertToDatabaseValue($vo, $this->platform);
        self::assertIsString($db);

        $restored = $type->convertToPHPValue($db, $this->platform);
        self::assertInstanceOf(ValueObject::class, $restored);
        self::assertTrue($vo->equals($restored));
    }

    public function testNullRoundTrips(): void
    {
        $type = Type::getType(EmailAddressType::NAME);

        self::assertNull($type->convertToDatabaseValue(null, $this->platform));
        self::assertNull($type->convertToPHPValue(null, $this->platform));
    }

    public function testWrongTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(EmailAddressType::NAME)->convertToDatabaseValue('not-a-vo', $this->platform);
    }

    public function testPortRoundTripsAsInteger(): void
    {
        $type = Type::getType(PortType::NAME);

        $db = $type->convertToDatabaseValue(Port::from(8080), $this->platform);
        self::assertSame(8080, $db);

        $restored = $type->convertToPHPValue($db, $this->platform);
        self::assertInstanceOf(Port::class, $restored);
        self::assertTrue(Port::from(8080)->equals($restored));
    }

    public function testGeoCoordinatesRoundTripsAsJson(): void
    {
        $type = Type::getType(GeoCoordinatesType::NAME);
        $coordinates = GeoCoordinates::fromFloats(51.5074, -0.1278);

        $db = $type->convertToDatabaseValue($coordinates, $this->platform);
        self::assertIsString($db);

        $restored = $type->convertToPHPValue($db, $this->platform);
        self::assertInstanceOf(GeoCoordinates::class, $restored);
        self::assertTrue($coordinates->equals($restored));
    }

    public function testSqlDeclarationIsNonEmpty(): void
    {
        self::assertNotSame('', Type::getType(EmailAddressType::NAME)->getSQLDeclaration([], $this->platform));
        self::assertNotSame('', Type::getType(PortType::NAME)->getSQLDeclaration([], $this->platform));
    }
}
