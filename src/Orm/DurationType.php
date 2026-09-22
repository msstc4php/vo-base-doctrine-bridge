<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Temporal\Duration;
use Override;

final class DurationType extends AbstractStringVoType
{
    public const string NAME = 'vo_duration';

    protected function valueObjectClass(): string
    {
        return Duration::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 40;
    }
}
