<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money;

use InvalidArgumentException;

/**
 * Raised when a value cannot be mapped by the money embeddables. Extends
 * {@see InvalidArgumentException} so callers catching that keep working, as
 * every relocated bridge type throws it.
 */
final class MoneyMappingException extends InvalidArgumentException
{
}
