<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Shared\Money;

use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Money;

/**
 * Decides which money mapping a given {@see Money} belongs to.
 *
 * A Money carries three things — amount, currency and Context — and two columns
 * express only the first two. So the context is what selects the mapping, and
 * the two contexts it cannot express are refused rather than silently degraded.
 *
 * This is the only part of the Shared layer that needs brick/money; the scale
 * rules in {@see MoneyScale} stay free of it.
 */
final class MoneyContextRouter
{
    private function __construct()
    {
    }

    /**
     * @throws MoneyMappingException when the context is not the currency's default scale
     */
    public static function assertBaseContext(Money $money): void
    {
        $context = $money->getContext();

        if ($context instanceof DefaultContext) {
            return;
        }

        if ($context instanceof CustomContext) {
            throw new MoneyMappingException(\sprintf('Money in a CustomContext (scale %d) does not belong to the two-column minor-unit mapping; use the high-precision embeddable instead.', $context->getScale()));
        }

        throw new MoneyMappingException(self::unmappableContextMessage($context));
    }

    /**
     * Widening is lossless and therefore silent: a Money at a smaller scale is
     * padded with zeros. Narrowing loses money, so a larger scale is refused
     * here and only an explicit method with an explicit rounding mode may do it.
     *
     * @param int<0, max> $targetScale
     *
     * @throws MoneyMappingException when the scale exceeds the target or the context is unmappable
     */
    public static function assertPreciseContext(Money $money, int $targetScale): void
    {
        $context = $money->getContext();

        if (!$context instanceof DefaultContext && !$context instanceof CustomContext) {
            throw new MoneyMappingException(self::unmappableContextMessage($context));
        }

        $scale = $money->getAmount()->getScale();

        if ($scale > $targetScale) {
            throw new MoneyMappingException(\sprintf('Money at scale %d exceeds the high-precision scale %d for %s; narrowing loses money and requires an explicit rounding mode.', $scale, $targetScale, $money->getCurrency()->getCurrencyCode()));
        }
    }

    private static function shortName(object $context): string
    {
        $fqcn = $context::class;
        $tail = \strrchr($fqcn, '\\');

        return $tail === false ? $fqcn : \substr($tail, 1);
    }

    private static function unmappableContextMessage(object $context): string
    {
        return \sprintf(
            '%s cannot be expressed by a two-column money mapping: the rounding step and the '
            . 'auto-determined scale live in the context, not in the value. Store such amounts in '
            . 'the single-column vo_money type — but note it does not preserve the context either: '
            . 'reading returns a CustomContext at the same scale, so the rounding step and the '
            . 'auto-scale are lost.',
            self::shortName($context),
        );
    }
}
