<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Primitive\PositiveInt;
use Override;

final class PositiveIntType extends AbstractIntVoType
{
    public const string NAME = 'vo_positive_int';

    #[Override]
    protected function valueObjectClass(): string
    {
        return PositiveInt::class;
    }
}
