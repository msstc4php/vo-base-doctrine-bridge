<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Temporal\Duration;

final class DurationType extends AbstractStringVoType
{
    public const string NAME = 'vo_duration';

    protected function valueObjectClass(): string
    {
        return Duration::class;
    }
}
