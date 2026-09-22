<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Odm;

use Doctrine\ODM\MongoDB\Types\Type;
use MSSTC4PHP\ValueObjectDoctrineBridge\Odm\TypeRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeRegistrar::class)]
final class TypeRegistrarTest extends TestCase
{
    public function testRegisterAllRegistersEveryType(): void
    {
        TypeRegistrar::registerAll();

        foreach (TypeRegistrar::types() as $name => $class) {
            self::assertTrue(Type::hasType($name), \sprintf('Type "%s" is not registered.', $name));
            self::assertInstanceOf($class, Type::getType($name));
        }
    }

    public function testRegisterAllIsIdempotent(): void
    {
        TypeRegistrar::registerAll();
        TypeRegistrar::registerAll();

        foreach (\array_keys(TypeRegistrar::types()) as $name) {
            self::assertTrue(Type::hasType($name));
        }
    }
}
