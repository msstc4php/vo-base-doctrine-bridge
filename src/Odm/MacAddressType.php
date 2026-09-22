<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\MacAddress;

final class MacAddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_mac_address';

    protected function valueObjectClass(): string
    {
        return MacAddress::class;
    }
}
