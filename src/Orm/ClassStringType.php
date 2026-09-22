<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Format\ClassString;
use Override;

final class ClassStringType extends AbstractStringVoType
{
    public const string NAME = 'vo_class_string';

    protected function valueObjectClass(): string
    {
        return ClassString::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 255;
    }
}
