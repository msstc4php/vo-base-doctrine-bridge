<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Finance\Iban;

final class IbanType extends AbstractStringVoType
{
    public const string NAME = 'vo_iban';

    protected function valueObjectClass(): string
    {
        return Iban::class;
    }
}
