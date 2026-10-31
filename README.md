# msstc4php/vo-base-doctrine-bridge

Doctrine ORM and MongoDB ODM types for [`msstc4php/vo-base`][core]:
37 field types per branch, plus two money embeddables.

No framework is a runtime dependency. Install the Doctrine stack you actually
use; the other branch never loads.

## Install (pre-release)

Neither this package nor the core is on Packagist yet. Check both out side by
side and point Composer at the core:

```json
{
    "repositories": [
        { "type": "path", "url": "../vo-base", "options": { "symlink": true } }
    ],
    "require": {
        "msstc4php/vo-base-doctrine-bridge": "dev-main"
    }
}
```

Composer derives a path package's version from the checked-out branch name, so
the core must be on the branch the constraint names.

## Registering the types

Without Symfony, once at bootstrap — idempotent, safe in long-running workers:

```php
use MSSTC4PHP\ValueObjectDoctrineBridge\Orm\TypeRegistrar;

TypeRegistrar::registerAll();
```

With Symfony, map `TypeRegistrar::types()` under `doctrine.dbal.types` instead,
or install [`msstc4symfony/vo-base-bridge`][bridge], whose bundle does it.

The ODM branch has its own `Odm\TypeRegistrar` with the same API and the same
type names — the same 37 keys on both sides.

## Mapping a field

```php
#[ORM\Column(type: 'vo_email_address')]
private EmailAddress $email;

#[ORM\Column(type: 'vo_percentage')]
private Percentage $discount;

// Backed enums need no custom type.
#[ORM\Column(enumType: CurrencyCode::class)]
private CurrencyCode $currency;
```

## Money

Three mappings, for three different jobs.

```php
// One VARCHAR, any context, nullable-friendly. No SQL aggregation.
#[ORM\Column(type: 'vo_money', nullable: true)]
private ?Money $total;

// Two columns: minor units plus currency. Aggregates in SQL.
#[ORM\Embedded(class: MoneyAmount::class)]
private MoneyAmount $subtotal;

// Two columns at two extra digits, for intermediate results.
#[ORM\Embedded(class: PreciseMoneyAmount::class)]
private PreciseMoneyAmount $unitPrice;
```

```php
$amount = MoneyAmount::fromBrickMoney(Money::of('1.23', 'USD'));
$amount->minorUnits;          // 123
$amount->toDecimalString();   // "1.23" -- works without brick/money
$amount->toBrickMoney();      // Money, back again

PreciseMoneyAmount::fromMoneyAmount($amount)->unscaledValue;     // 12300
$precise->toMoneyAmount(RoundingMode::HalfEven)->minorUnits;     // rounding is explicit
```

**Choosing between them**, and the two things that will otherwise surprise you —
an embeddable field cannot be nullable, and SQL aggregation is only valid within
one currency — are in
[`.claude/docs/integrations.md`](.claude/docs/integrations.md).

## Scale comes from ISO 4217

The scale of a money column is `CurrencyCode::isoMinorUnits()`, never the CLDR
display digits. The two differ for 16 currencies: 1000 HUF is 100000 minor
units by ISO and would be 1000 by CLDR, so mixing them shifts stored amounts by
two orders of magnitude. A contract test pins the core's table against
brick/money's.

## Development

```
make check   # php -l, PHPStan level 9, cs-fixer, composer validate/audit, Rector, deptrac
make test    # PHPUnit
make fix     # cs-fixer + Rector, applied
```

[core]: ../vo-base
[bridge]: ../../msstc4symfony/vo-base-bridge-symfony
