<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Orm;

use MSSTC4PHP\ValueObject\Format\JsonString;

final class JsonStringType extends AbstractTextVoType
{
    public const string NAME = 'vo_json_string';

    protected function valueObjectClass(): string
    {
        return JsonString::class;
    }
}
