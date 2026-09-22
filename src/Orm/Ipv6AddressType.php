<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use Override;

final class Ipv6AddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_ipv6_address';

    protected function valueObjectClass(): string
    {
        return Ipv6Address::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 45;
    }
}
