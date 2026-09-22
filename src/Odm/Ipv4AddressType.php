<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\Ipv4Address;

final class Ipv4AddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_ipv4_address';

    protected function valueObjectClass(): string
    {
        return Ipv4Address::class;
    }
}
