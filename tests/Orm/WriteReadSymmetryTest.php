<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Orm;

use Brick\Money\Money;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\TypeRegistrar;
use MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Support\NonCanonicalSamples;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every registered type must be able to read back what it writes, checked on a
 * deliberately awkward value rather than a canonical one.
 *
 * This exists because the two defects found so far -- BrickMoneyType and
 * DateRangeType -- both passed the canonical round-trip tests for months. A
 * per-type argument about "single column, string-backed, nothing lossy" is not
 * evidence; this is.
 */
#[CoversNothing]
final class WriteReadSymmetryTest extends TestCase
{
    private SQLitePlatform $platform;

    protected function setUp(): void
    {
        TypeRegistrar::registerAll();
        $this->platform = new SQLitePlatform();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function everyRegisteredType(): iterable
    {
        foreach (\array_keys(TypeRegistrar::types()) as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('everyRegisteredType')]
    public function testWriteReadIsIdentityOnANonCanonicalValue(string $typeName): void
    {
        $samples = NonCanonicalSamples::all();

        self::assertArrayHasKey(
            $typeName,
            $samples,
            \sprintf('Type "%s" has no non-canonical sample. Add one; the class of defect it guards against is invisible to canonical round-trips.', $typeName),
        );

        $sample = $samples[$typeName];
        $type = Type::getType($typeName);

        $column = $type->convertToDatabaseValue($sample, $this->platform);
        $back = $type->convertToPHPValue($column, $this->platform);

        if ($sample instanceof Money) {
            self::assertInstanceOf(Money::class, $back);
            self::assertTrue($sample->isEqualTo($back), $typeName);
            self::assertSame(
                $sample->getAmount()->getScale(),
                $back->getAmount()->getScale(),
                $typeName . ': scale must survive, otherwise trailing zeros are lost',
            );

            return;
        }

        self::assertInstanceOf(ValueObject::class, $back);
        self::assertTrue($sample->equals($back), $typeName);
    }

    public function testEveryRegisteredTypeHasASample(): void
    {
        $registered = \array_keys(TypeRegistrar::types());
        $sampled = \array_keys(NonCanonicalSamples::all());
        \sort($registered);
        \sort($sampled);

        self::assertSame($registered, $sampled, 'Sample map and registry must stay in step.');
    }
}
