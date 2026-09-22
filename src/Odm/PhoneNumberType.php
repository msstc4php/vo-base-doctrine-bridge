<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Format\PhoneNumber;

final class PhoneNumberType extends AbstractStringVoType
{
    public const string NAME = 'vo_phone_number';

    protected function valueObjectClass(): string
    {
        return PhoneNumber::class;
    }
}
