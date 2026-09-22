# Integrations

## Doctrine ORM — `src/Orm/`

- `AbstractStringVoType` — one generic DBAL `Type` mapping any
  `StringConvertible` VO to VARCHAR. A concrete type only names its VO class
  and optionally a column length. **DBAL 4:** do not implement `getName()` or
  `requiresSQLCommentHint()` — both removed; type identity is the registered name.
- `AbstractTextVoType` (CLOB) for large text VOs — `Base64String`, `JsonString`.
- `AbstractIntVoType` for `PositiveInt` and `NonNegativeInt`.
- Standalone: `PortType` (integer), `PercentageType` (float),
  `GeoCoordinatesType`/`DateRangeType`/`BoundedStringType`/`IntRangeType`
  (one JSON column), `BrickMoneyType`.
- `TypeRegistrar::registerAll()` — idempotent via `Type::hasType`; `types()`
  returns the full name → class map. Bare Doctrine calls it at bootstrap;
  Symfony maps under `doctrine.dbal.types` instead.
- Backed enums (`CountryCode`/`CurrencyCode`/`LanguageCode`) use Doctrine's
  native `enumType` — no custom type, by design.

## Doctrine MongoDB ODM — `src/Odm/`

Same shape on the ODM `Type` base. Differences: converters take **no
`$platform`**, there is **no `getSQLDeclaration`**, each type uses
`ClosureToPHP`, and composite types store a **native sub-document array** rather
than a JSON string. `AbstractTextVoType` has no counterpart — a BSON string does
not distinguish VARCHAR from CLOB. Registration goes through
`Type::registerType`, guarded by `Type::hasType`.

`Type`'s constructor is private: instances come from `Type::getType($name)`.

## Money — three mappings, and how to choose

| | `vo_money` | `MoneyAmount` | `PreciseMoneyAmount` |
|---|---|---|---|
| Shape | one VARCHAR | two columns | two columns |
| Stores | `"USD 1.2345"` | minor units + currency | unscaled value + currency |
| Scale | whatever was written | `isoMinorUnits()` | `isoMinorUnits() + 2` |
| For | any context, including cash and auto | ordinary amounts, aggregated in SQL | intermediate results, sub-cent prices |
| Accepts | any `Money` | `DefaultContext` only | `CustomContext` at the target scale, or anything narrower |
| On mismatch | — | throws, naming the right type | throws, naming the right type |
| Optional (nullable) field | **yes** | **no** | **no** |
| Aggregation in the database | no | yes, within one currency | yes, within one currency |

**The nullable row is the one that bites silently.** A context mismatch throws
with a message pointing at the right type; a nullable embeddable does not throw
at all — it hydrates into an object with uninitialised properties that fails
later, somewhere else. See `known-issues.md`.

### Moving between them

- `MoneyAmount` → `PreciseMoneyAmount`: `fromMoneyAmount()`, a silent ×100
  (the surplus is always two digits, whatever the currency).
- `PreciseMoneyAmount` → `MoneyAmount`: `toMoneyAmount(RoundingMode)`. The
  argument is required and has no default — narrowing loses money.
- `vo_money` → either: parse the string and route through
  `MoneyContextRouter`. Values written in a `CashContext` or `AutoContext` are
  **not portable**: the rounding step and the auto-scale live in the context,
  which neither two-column mapping stores.

### Aggregating the two-column mappings

`SUM` and `AVG` over the integer column are only meaningful **within one
currency**. The scale is derived from the currency, so `JPY 1234` sits as
`1234` and `USD 1.23` as `123`; summing across currencies returns a plausible,
meaningless number.

Group or filter on the currency column. A raw column value cannot be
interpreted without it, and `minorUnits` and `unscaledValue` are not comparable
with each other.
