<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Format\Slug;

final class SlugType extends AbstractStringVoType
{
    public const string NAME = 'vo_slug';

    protected function valueObjectClass(): string
    {
        return Slug::class;
    }
}
