<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Orm;

use Doctrine\DBAL\Types\Type;
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\TypeRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeRegistrar::class)]
final class TypeRegistrarTest extends TestCase
{
    public function testRegistersEveryTypeAndIsIdempotent(): void
    {
        TypeRegistrar::registerAll();
        TypeRegistrar::registerAll(); // second call must not throw

        foreach (\array_keys(TypeRegistrar::types()) as $name) {
            self::assertTrue(Type::hasType($name), \sprintf('Type "%s" was not registered.', $name));
        }
    }
}
