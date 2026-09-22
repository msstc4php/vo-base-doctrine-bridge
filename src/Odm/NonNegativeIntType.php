<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Primitive\NonNegativeInt;
use Override;

final class NonNegativeIntType extends AbstractIntVoType
{
    public const string NAME = 'vo_non_negative_int';

    #[Override]
    protected function valueObjectClass(): string
    {
        return NonNegativeInt::class;
    }
}
