<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use Override;

final class DecimalType extends AbstractStringVoType
{
    public const string NAME = 'vo_decimal';

    protected function valueObjectClass(): string
    {
        return Decimal::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 65;
    }
}
