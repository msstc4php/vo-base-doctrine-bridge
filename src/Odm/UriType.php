<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Network\Uri;

final class UriType extends AbstractStringVoType
{
    public const string NAME = 'vo_uri';

    protected function valueObjectClass(): string
    {
        return Uri::class;
    }
}
