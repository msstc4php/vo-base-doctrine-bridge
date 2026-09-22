<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPositiveIntException;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\NonEmptyString;
use MSSTC4PHP\ValueObject\Primitive\NonNegativeInt;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Primitive\PositiveInt;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\AbstractIntVoType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\BoundedStringType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\IntRangeType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\NonEmptyStringType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\NonNegativeIntType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\PercentageType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\PositiveIntType;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\TypeRegistrar;
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
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->platform = new SQLitePlatform();
    }

    /**
     * @return iterable<string, array{Type, ValueObject}>
     */
    public static function roundTripProvider(): iterable
    {
        yield 'NonEmptyString' => [new NonEmptyStringType(), NonEmptyString::from('hello')];
        yield 'PositiveInt' => [new PositiveIntType(), PositiveInt::from(42)];
        yield 'NonNegativeInt zero' => [new NonNegativeIntType(), NonNegativeInt::from(0)];
        yield 'Percentage fractional' => [new PercentageType(), Percentage::from(12.345)];
        yield 'Percentage zero' => [new PercentageType(), Percentage::from(0.0)];
        yield 'BoundedString bounded' => [new BoundedStringType(), new BoundedString('abc', 1, 10)];
        // maxLength defaults to null, so an unbounded string is the common case,
        // not an edge: the null key must survive rather than be dropped.
        yield 'BoundedString unbounded' => [new BoundedStringType(), new BoundedString('abc')];
        yield 'IntRange negative min' => [new IntRangeType(), new IntRange(-5, 5)];
        yield 'IntRange zero width' => [new IntRangeType(), new IntRange(7, 7)];
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTripIsIdentity(Type $type, ValueObject $vo): void
    {
        $column = $type->convertToDatabaseValue($vo, $this->platform);
        $back = $type->convertToPHPValue($column, $this->platform);

        self::assertInstanceOf(ValueObject::class, $back);
        self::assertTrue($vo->equals($back), 'Round-trip must be identity by equals(), not by string form.');
    }

    /**
     * @return iterable<string, array{Type}>
     */
    public static function typeProvider(): iterable
    {
        yield 'NonEmptyString' => [new NonEmptyStringType()];
        yield 'PositiveInt' => [new PositiveIntType()];
        yield 'NonNegativeInt' => [new NonNegativeIntType()];
        yield 'Percentage' => [new PercentageType()];
        yield 'BoundedString' => [new BoundedStringType()];
        yield 'IntRange' => [new IntRangeType()];
    }

    #[DataProvider('typeProvider')]
    public function testNullPassesThroughBothConverters(Type $type): void
    {
        self::assertNull($type->convertToDatabaseValue(null, $this->platform));
        self::assertNull($type->convertToPHPValue(null, $this->platform));
    }

    /**
     * @return iterable<string, array{Type, ValueObject}>
     */
    public static function foreignValueProvider(): iterable
    {
        // Percentage is not StringConvertible, so it is foreign to the string type;
        // NonEmptyString is foreign to every other one.
        yield 'NonEmptyString type' => [new NonEmptyStringType(), Percentage::from(1.0)];
        yield 'PositiveInt type' => [new PositiveIntType(), NonEmptyString::from('x')];
        yield 'NonNegativeInt type' => [new NonNegativeIntType(), NonEmptyString::from('x')];
        yield 'Percentage type' => [new PercentageType(), NonEmptyString::from('x')];
        yield 'BoundedString type' => [new BoundedStringType(), NonEmptyString::from('x')];
        yield 'IntRange type' => [new IntRangeType(), NonEmptyString::from('x')];
    }

    #[DataProvider('foreignValueProvider')]
    public function testForeignValueObjectIsRejected(Type $type, ValueObject $foreign): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type->convertToDatabaseValue($foreign, $this->platform);
    }

    public function testBoundedStringKeepsItsBounds(): void
    {
        $type = new BoundedStringType();
        $bounded = new BoundedString('abc', 1, 10);

        $column = $type->convertToDatabaseValue($bounded, $this->platform);
        self::assertIsString($column);

        $decoded = \json_decode($column, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(['value', 'minLength', 'maxLength'], \array_keys($decoded));

        $back = $type->convertToPHPValue($column, $this->platform);
        self::assertInstanceOf(BoundedString::class, $back);
        self::assertSame(1, $back->minLength);
        self::assertSame(10, $back->maxLength);
        // Guards against delegating to jsonSerialize(), which returns the bare
        // string: that copy would still match on __toString but not on equals().
        self::assertFalse(new BoundedString('abc')->equals($back));
    }

    public function testBoundedStringKeepsNullMaxLengthKey(): void
    {
        $column = new BoundedStringType()->convertToDatabaseValue(new BoundedString('abc'), $this->platform);
        self::assertIsString($column);

        $decoded = \json_decode($column, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('maxLength', $decoded);
        self::assertNull($decoded['maxLength']);
    }

    public function testIntRangeRejectsPayloadWithoutMax(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IntRangeType()->convertToPHPValue('{"min":1}', $this->platform);
    }

    public function testBoundedStringRejectsPayloadWithoutMinLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BoundedStringType()->convertToPHPValue('{"value":"abc"}', $this->platform);
    }

    /**
     * Reading a zero out of the column must surface the value object's own
     * invariant failure rather than hand back an invalid object.
     */
    public function testPositiveIntRejectsZeroFromColumn(): void
    {
        $this->expectException(InvalidPositiveIntException::class);

        new PositiveIntType()->convertToPHPValue(0, $this->platform);
    }

    #[DataProvider('typeProvider')]
    public function testNonNumericColumnValueIsRejected(Type $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type->convertToPHPValue([], $this->platform);
    }

    public function testColumnDeclarations(): void
    {
        $float = new PercentageType()->getSQLDeclaration([], $this->platform);
        self::assertStringNotContainsStringIgnoringCase('VARCHAR', $float);
        self::assertStringNotContainsStringIgnoringCase('INT', $float);

        foreach ([new BoundedStringType(), new IntRangeType()] as $composite) {
            $declaration = $composite->getSQLDeclaration([], $this->platform);
            self::assertStringNotContainsStringIgnoringCase('VARCHAR', $declaration);
        }

        foreach ([new PositiveIntType(), new NonNegativeIntType()] as $int) {
            self::assertStringContainsStringIgnoringCase('INT', $int->getSQLDeclaration([], $this->platform));
        }
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
