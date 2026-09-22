<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObjectDoctrineBridge\Tests\Contract;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Composer\InstalledVersions;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The money mapping takes ISO 4217 minor units from the core enum and never
 * from brick/money, so that one process holds one table. This pins the two
 * against each other: a drift between them silently shifts stored amounts by
 * orders of magnitude.
 *
 * A red run usually means the two repositories resolved different brick/money
 * versions rather than bad data, so the failure message carries the installed
 * version. Check that first, then regenerate the core enum.
 */
#[CoversNothing]
final class IsoMinorUnitsParityTest extends TestCase
{
    public function testEveryCurrencyKnownToBrickMatchesTheCore(): void
    {
        $mismatches = [];
        $compared = 0;

        foreach (CurrencyCode::cases() as $currency) {
            try {
                $brick = Currency::of($currency->value)->getDefaultFractionDigits();
            } catch (UnknownCurrencyException) {
                self::assertNull(
                    $currency->isoMinorUnits(),
                    \sprintf('%s: the core claims ISO minor units for a code brick/money does not know.', $currency->value),
                );

                continue;
            }

            $compared++;

            if ($currency->isoMinorUnits() !== $brick) {
                $mismatches[] = \sprintf(
                    '%s: core %s, brick/money %d',
                    $currency->value,
                    \var_export($currency->isoMinorUnits(), true),
                    $brick,
                );
            }
        }

        self::assertSame(
            [],
            $mismatches,
            \sprintf(
                "ISO minor units drifted from brick/money %s across %d compared currencies:\n%s",
                $this->brickMoneyVersion(),
                $compared,
                \implode("\n", $mismatches),
            ),
        );
    }

    public function testBrickCurrenciesAreASubsetOfTheCoreEnum(): void
    {
        $unknownToCore = [];

        $table = require __DIR__ . '/../../vendor/brick/money/data/iso-currencies.php';
        self::assertIsArray($table);

        foreach (\array_keys($table) as $code) {
            if (CurrencyCode::tryFrom((string) $code) === null) {
                $unknownToCore[] = (string) $code;
            }
        }

        self::assertSame([], $unknownToCore, 'Every currency brick/money knows must exist in the core enum.');
    }

    private function brickMoneyVersion(): string
    {
        return InstalledVersions::getPrettyVersion('brick/money') ?? 'unknown';
    }
}
