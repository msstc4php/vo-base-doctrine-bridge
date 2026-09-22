<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Finance\Iban;
use Override;

final class IbanType extends AbstractStringVoType
{
    public const string NAME = 'vo_iban';

    protected function valueObjectClass(): string
    {
        return Iban::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 34;
    }
}
