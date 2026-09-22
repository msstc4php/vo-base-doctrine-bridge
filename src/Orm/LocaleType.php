<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\I18n\Locale;
use Override;

final class LocaleType extends AbstractStringVoType
{
    public const string NAME = 'vo_locale';

    protected function valueObjectClass(): string
    {
        return Locale::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 10;
    }
}
