<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Network\Hostname;
use Override;

final class HostnameType extends AbstractStringVoType
{
    public const string NAME = 'vo_hostname';

    protected function valueObjectClass(): string
    {
        return Hostname::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 253;
    }
}
