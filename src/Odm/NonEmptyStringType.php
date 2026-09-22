<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Primitive\NonEmptyString;
use Override;

final class NonEmptyStringType extends AbstractStringVoType
{
    public const string NAME = 'vo_non_empty_string';

    #[Override]
    protected function valueObjectClass(): string
    {
        return NonEmptyString::class;
    }
}
