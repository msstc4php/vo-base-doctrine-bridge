<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use Override;

final class CreditCardNumberType extends AbstractStringVoType
{
    public const string NAME = 'vo_credit_card_number';

    protected function valueObjectClass(): string
    {
        return CreditCardNumber::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 19;
    }
}
