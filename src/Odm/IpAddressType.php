<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\IpAddress;

final class IpAddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_ip_address';

    protected function valueObjectClass(): string
    {
        return IpAddress::class;
    }
}
