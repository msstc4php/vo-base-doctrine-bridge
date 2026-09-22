# Conventions

Project-specific choices that look arbitrary until you know why.

## The two branches are deliberately not unified

`Orm` and `Odm` hold near-identical class names with near-identical bodies. That
duplication is the design: a shared base would be a horizontal edge between the
branches, which deptrac forbids and which the CI isolation jobs exist to keep
impossible. Only genuinely framework-free logic goes to `Shared`.

## Shape differences between the branches

| | ORM | ODM |
|---|---|---|
| Converter signature | takes `AbstractPlatform` | takes nothing |
| Schema declaration | `getSQLDeclaration()` | none |
| Hydration closure | — | `use ClosureToPHP` |
| Composite storage | one JSON string column | native sub-document array |
| `AbstractTextVoType` | exists (CLOB vs VARCHAR) | absent — a BSON string has no such split |
| Type instantiation | `new XType()` works | constructor is private; use `Type::getType()` |

That last row bites in tests: an ODM type cannot be constructed directly, and a
PHPUnit data provider runs *before* `setUp()`, so providers must yield type
names and resolve them inside the test.

## `GeoCoordinatesType` is one column, not an embeddable

It predates the money embeddables and stores lat/lng as a single JSON column.
Left as is: the relocation proved it byte-identical to the original, and
changing it serves no requirement.

## Abstract bases are earned, not assumed

`AbstractIntVoType` exists because two types per branch use it. `Percentage`,
`BoundedString` and `IntRange` are standalone — a base with one implementation
is the same standalone shape spread over two files.

## Money logic never reads brick/money's currency table

Scale comes from the core's `CurrencyCode::isoMinorUnits()`. Two tables of the
same fact in one process is the defect the whole money design exists to avoid.
`tests/Contract/IsoMinorUnitsParityTest.php` pins them together.
