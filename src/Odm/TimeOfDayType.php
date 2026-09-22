<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;

final class TimeOfDayType extends AbstractStringVoType
{
    public const string NAME = 'vo_time_of_day';

    protected function valueObjectClass(): string
    {
        return TimeOfDay::class;
    }
}
