<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Format\JsonString;

final class JsonStringType extends AbstractStringVoType
{
    public const string NAME = 'vo_json_string';

    protected function valueObjectClass(): string
    {
        return JsonString::class;
    }
}
