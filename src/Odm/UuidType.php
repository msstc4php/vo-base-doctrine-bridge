<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Identity\Uuid;

final class UuidType extends AbstractStringVoType
{
    public const string NAME = 'vo_uuid';

    protected function valueObjectClass(): string
    {
        return Uuid::class;
    }
}
