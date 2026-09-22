<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\Cidr;

final class CidrType extends AbstractStringVoType
{
    public const string NAME = 'vo_cidr';

    protected function valueObjectClass(): string
    {
        return Cidr::class;
    }
}
