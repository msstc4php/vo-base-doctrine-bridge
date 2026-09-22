# Architecture

Three layers, enforced by `deptrac.yaml`.

```
Shared  — money rules: scale, int64 bound, context routing
  ^                    ^
  |                    |
 Orm                  Odm
```

- **`Shared`** depends on the core only. The core is not declared as a deptrac
  layer, which is why it does not appear in the ruleset.
- **`Orm`** and **`Odm`** each depend on `Shared`. They never reference each
  other — that absence is the machine form of the branch split, and deptrac
  fails the build if it appears.

## Why `Shared` exists

Four money embeddables (base and high-precision, in each branch) share the same
rules: which scale a currency maps at, whether a value fits the integer column,
which mapping a `Money` belongs to. Duplicating that four times was the
alternative; linking `Orm` to `Odm` is forbidden. A third layer both branches
depend on is the only shape left.

## Autoload safety

No framework is a runtime dependency. `doctrine/dbal`, `doctrine/orm`,
`doctrine/mongodb-odm` and `brick/money` live in `require-dev` plus `suggest`.

This is proven per push, not asserted: CI removes one Doctrine stack and loads
every class of the other branch through reflection. Measured on the current
tree — 43 ORM classes and 3 Shared classes load without `mongodb-odm`; 42 ODM
and the same 3 load without `orm`/`dbal`.

Reflection resolves parents, interfaces and traits, not signature or body
types. So that job proves loadability; the `Orm`/`Odm` separation is deptrac's
job, not its.

## Layout

```
src/
├── Shared/Money/   MoneyScale, MoneyContextRouter, MoneyMappingException
├── Orm/            37 DBAL types, 2 abstract bases, TypeRegistrar
│   └── Embeddable/ MoneyAmount, PreciseMoneyAmount
└── Odm/            37 ODM types, 2 abstract bases, TypeRegistrar
    └── Embeddable/ MoneyAmount, PreciseMoneyAmount
```
