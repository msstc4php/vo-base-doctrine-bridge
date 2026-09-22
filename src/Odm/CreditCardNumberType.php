<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;

final class CreditCardNumberType extends AbstractStringVoType
{
    public const string NAME = 'vo_credit_card_number';

    protected function valueObjectClass(): string
    {
        return CreditCardNumber::class;
    }
}
