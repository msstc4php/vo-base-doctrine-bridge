<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Odm;

use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPositiveIntException;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\NonEmptyString;
use MSSTC4PHP\ValueObject\Primitive\NonNegativeInt;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Primitive\PositiveInt;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\AbstractIntVoType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\BoundedStringType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\IntRangeType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\NonEmptyStringType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\NonNegativeIntType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\PercentageType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\PositiveIntType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\TypeRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractIntVoType::class)]
#[CoversClass(PositiveIntType::class)]
#[CoversClass(NonNegativeIntType::class)]
#[CoversClass(NonEmptyStringType::class)]
#[CoversClass(PercentageType::class)]
#[CoversClass(BoundedStringType::class)]
#[CoversClass(IntRangeType::class)]
final class PrimitiveTypesTest extends TestCase
{
    protected function setUp(): void
    {
        // ODM's Type constructor is private; instances come from the registry.
        TypeRegistrar::registerAll();
    }

    /**
     * @return iterable<string, array{string, ValueObject}>
     */
    public static function roundTripProvider(): iterable
    {
        yield 'NonEmptyString' => [NonEmptyStringType::NAME, NonEmptyString::from('hello')];
        yield 'PositiveInt' => [PositiveIntType::NAME, PositiveInt::from(42)];
        yield 'NonNegativeInt zero' => [NonNegativeIntType::NAME, NonNegativeInt::from(0)];
        yield 'Percentage fractional' => [PercentageType::NAME, Percentage::from(12.345)];
        yield 'BoundedString bounded' => [BoundedStringType::NAME, new BoundedString('abc', 1, 10)];
        yield 'BoundedString unbounded' => [BoundedStringType::NAME, new BoundedString('abc')];
        yield 'IntRange negative min' => [IntRangeType::NAME, new IntRange(-5, 5)];
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTripIsIdentity(string $typeName, ValueObject $vo): void
    {
        $type = Type::getType($typeName);
        $back = $type->convertToPHPValue($type->convertToDatabaseValue($vo));

        self::assertInstanceOf(ValueObject::class, $back);
        self::assertTrue($vo->equals($back));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function typeProvider(): iterable
    {
        yield 'NonEmptyString' => [NonEmptyStringType::NAME];
        yield 'PositiveInt' => [PositiveIntType::NAME];
        yield 'NonNegativeInt' => [NonNegativeIntType::NAME];
        yield 'Percentage' => [PercentageType::NAME];
        yield 'BoundedString' => [BoundedStringType::NAME];
        yield 'IntRange' => [IntRangeType::NAME];
    }

    #[DataProvider('typeProvider')]
    public function testNullPassesThroughBothConverters(string $typeName): void
    {
        $type = Type::getType($typeName);

        self::assertNull($type->convertToDatabaseValue(null));
        self::assertNull($type->convertToPHPValue(null));
    }

    /**
     * @return iterable<string, array{string, ValueObject}>
     */
    public static function foreignValueProvider(): iterable
    {
        yield 'NonEmptyString type' => [NonEmptyStringType::NAME, Percentage::from(1.0)];
        yield 'PositiveInt type' => [PositiveIntType::NAME, NonEmptyString::from('x')];
        yield 'NonNegativeInt type' => [NonNegativeIntType::NAME, NonEmptyString::from('x')];
        yield 'Percentage type' => [PercentageType::NAME, NonEmptyString::from('x')];
        yield 'BoundedString type' => [BoundedStringType::NAME, NonEmptyString::from('x')];
        yield 'IntRange type' => [IntRangeType::NAME, NonEmptyString::from('x')];
    }

    #[DataProvider('foreignValueProvider')]
    public function testForeignValueObjectIsRejected(string $typeName, ValueObject $foreign): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType($typeName)->convertToDatabaseValue($foreign);
    }

    /**
     * BSON stores documents natively, so the composite types keep an array
     * rather than a JSON string -- mirroring DateRangeType in this branch.
     */
    public function testCompositeTypesStoreSubDocuments(): void
    {
        $bounded = Type::getType(BoundedStringType::NAME)->convertToDatabaseValue(new BoundedString('abc', 1, 10));
        self::assertSame(['value' => 'abc', 'minLength' => 1, 'maxLength' => 10], $bounded);

        $range = Type::getType(IntRangeType::NAME)->convertToDatabaseValue(new IntRange(-5, 5));
        self::assertSame(['min' => -5, 'max' => 5], $range);
    }

    public function testBoundedStringKeepsNullMaxLengthKey(): void
    {
        $stored = Type::getType(BoundedStringType::NAME)->convertToDatabaseValue(new BoundedString('abc'));

        self::assertIsArray($stored);
        self::assertArrayHasKey('maxLength', $stored);
        self::assertNull($stored['maxLength']);
    }

    public function testBoundedStringKeepsItsBounds(): void
    {
        $type = Type::getType(BoundedStringType::NAME);
        $back = $type->convertToPHPValue($type->convertToDatabaseValue(new BoundedString('abc', 1, 10)));

        self::assertInstanceOf(BoundedString::class, $back);
        self::assertSame(1, $back->minLength);
        self::assertSame(10, $back->maxLength);
        self::assertFalse(new BoundedString('abc')->equals($back));
    }

    public function testIntRangeRejectsSubDocumentWithoutMax(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(IntRangeType::NAME)->convertToPHPValue(['min' => 1]);
    }

    public function testPositiveIntRejectsZeroFromDatabase(): void
    {
        $this->expectException(InvalidPositiveIntException::class);

        Type::getType(PositiveIntType::NAME)->convertToPHPValue(0);
    }

    public function testRegistrarCarriesEveryPrimitiveType(): void
    {
        $types = TypeRegistrar::types();

        foreach ([
            NonEmptyStringType::NAME,
            PositiveIntType::NAME,
            NonNegativeIntType::NAME,
            PercentageType::NAME,
            BoundedStringType::NAME,
            IntRangeType::NAME,
        ] as $name) {
            self::assertArrayHasKey($name, $types);
        }

        self::assertCount(37, $types);
    }
}
