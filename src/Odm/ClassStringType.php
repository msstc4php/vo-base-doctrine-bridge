<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Format\ClassString;

final class ClassStringType extends AbstractStringVoType
{
    public const string NAME = 'vo_class_string';

    protected function valueObjectClass(): string
    {
        return ClassString::class;
    }
}
