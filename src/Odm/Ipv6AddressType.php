<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\Ipv6Address;

final class Ipv6AddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_ipv6_address';

    protected function valueObjectClass(): string
    {
        return Ipv6Address::class;
    }
}
