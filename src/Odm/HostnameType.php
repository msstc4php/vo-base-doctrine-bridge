<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\Hostname;

final class HostnameType extends AbstractStringVoType
{
    public const string NAME = 'vo_hostname';

    protected function valueObjectClass(): string
    {
        return Hostname::class;
    }
}
