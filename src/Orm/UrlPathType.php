<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Network\UrlPath;
use Override;

final class UrlPathType extends AbstractStringVoType
{
    public const string NAME = 'vo_url_path';

    protected function valueObjectClass(): string
    {
        return UrlPath::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 2048;
    }
}
