<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Odm;

use MSSTC4PHP\ValueObject\Format\HexColor;

final class HexColorType extends AbstractStringVoType
{
    public const string NAME = 'vo_hex_color';

    protected function valueObjectClass(): string
    {
        return HexColor::class;
    }
}
