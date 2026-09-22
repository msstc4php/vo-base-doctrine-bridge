<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Override;

/**
 * Maps a string-backed value object to an unbounded CLOB/TEXT column instead of
 * a length-limited VARCHAR. Conversion is inherited unchanged from
 * {@see AbstractStringVoType}; only the SQL declaration differs.
 */
abstract class AbstractTextVoType extends AbstractStringVoType
{
    /**
     * @param array<string, mixed> $column
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }
}
