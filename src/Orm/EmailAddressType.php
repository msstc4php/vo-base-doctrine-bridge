<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use Override;

final class EmailAddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_email_address';

    protected function valueObjectClass(): string
    {
        return EmailAddress::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 255;
    }
}
