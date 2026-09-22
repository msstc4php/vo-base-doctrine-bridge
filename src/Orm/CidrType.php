<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Network\Cidr;
use Override;

final class CidrType extends AbstractStringVoType
{
    public const string NAME = 'vo_cidr';

    protected function valueObjectClass(): string
    {
        return Cidr::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 43;
    }
}
