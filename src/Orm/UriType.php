<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Network\Uri;
use Override;

final class UriType extends AbstractStringVoType
{
    public const string NAME = 'vo_uri';

    protected function valueObjectClass(): string
    {
        return Uri::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 2048;
    }
}
