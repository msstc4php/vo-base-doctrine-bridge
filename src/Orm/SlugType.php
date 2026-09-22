<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Format\Slug;
use Override;

final class SlugType extends AbstractStringVoType
{
    public const string NAME = 'vo_slug';

    protected function valueObjectClass(): string
    {
        return Slug::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 255;
    }
}
