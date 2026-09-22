<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\I18n\Locale;

final class LocaleType extends AbstractStringVoType
{
    public const string NAME = 'vo_locale';

    protected function valueObjectClass(): string
    {
        return Locale::class;
    }
}
