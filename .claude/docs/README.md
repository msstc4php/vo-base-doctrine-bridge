# Internal knowledge base

Engineer notes for `msstc4php/vo-base-doctrine-bridge`. Team-readable, not
user-facing documentation.

| File | What it answers |
|---|---|
| [architecture.md](architecture.md) | Layers, the deptrac rule, how autoload safety is proven |
| [integrations.md](integrations.md) | Per-branch type shapes; the three money mappings and how to choose |
| [conventions.md](conventions.md) | Choices that look arbitrary — branch duplication, abstract bases, ODM test quirks |
| [known-issues.md](known-issues.md) | Traps that cost time: the `ofMinor` scale bug, the two write/read asymmetries, CLDR vs ISO digits, the Rector/deptrac syntax conflict |

Related: the core's own `.claude/docs/`, and
`../vo-base-symfony-bridge/.claude/docs/` for the bundle.
