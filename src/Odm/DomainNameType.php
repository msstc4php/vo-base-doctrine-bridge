<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\DomainName;

final class DomainNameType extends AbstractStringVoType
{
    public const string NAME = 'vo_domain_name';

    protected function valueObjectClass(): string
    {
        return DomainName::class;
    }
}
