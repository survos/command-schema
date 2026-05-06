# command-schema — session handoff

Working notes for resuming on another machine. Move/delete once consolidated.

## What's working

- `survos/command-schema` library at `mono/lib/command-schema/` (new package).
  - `src/Schema/` — `CommandSchema`, `ArgumentSchema`, `OptionSchema`, `PromptSchema` (DTOs, `final readonly`, `Survos\CommandSchema\` namespace).
  - `src/Introspector/CommandIntrospector.php` — walks `Application::all()` → `CommandSchema[]`. Skips alias entries. Optional `onError` callback for resilient introspection of broken `LazyCommand`s.
  - `src/Tui/Palette.php` — skeleton TUI built on `symfony/tui`. Sidebar of all commands sorted by name, help pane formatted from schema, `↑↓ / Tab / q` keybindings.
  - `bin/commands` — env-var bootstrap (`KERNEL_CLASS`, `APP_ENV`, `APP_DEBUG`), `.env` loaded if `symfony/dotenv` present, `FrameworkBundle\Console\Application` discovers commands. Auto-detects `symfony/tui` + TTY → launches Palette; falls through to plain-text dump otherwise. `NO_TUI=1` forces dump mode.
  - `tests/IntrospectorTest.php` — 3 tests, 27 assertions, **green**. Run via `vendor/bin/phpunit -c phpunit.xml.dist` from inside the lib.
  - composer: `php ^8.4`, `symfony/console ^8.0` (loose for now; tighten to ^8.1 when `#[Ask]` reflection lands), `symfony/tui` in **suggest**, phpunit ^13 in dev.
  - `bin: ["bin/commands"]` declared so consumers get `vendor/bin/commands`.

- Dump mode verified in two consumers:
  - **md** — 315 commands across 63 namespaces, zero failures (after babel fix below).
  - **tui-monitor** — 124 commands across 20 namespaces, zero failures.

- TUI mode: `Palette` autoloads cleanly under tui-monitor's `symfony/tui` environment. **Not yet visually verified** — needs interactive terminal. Run:
  ```bash
  cd ~/sites/tui-monitor && vendor/bin/commands
  ```

## Side fixes shipped this session

- `bu/babel-bundle/src/Attribute/StorageMode.php` — **new file**. Extracted `enum StorageMode` from inside `BabelStorage.php`; PSR-4 couldn't resolve it directly because two top-level types lived in one file, so direct lookups for `Survos\BabelBundle\Attribute\StorageMode` fataled unless `BabelStorage` was already loaded. Surfaced because the introspector forces every `LazyCommand` to resolve.
- `bu/babel-bundle/src/Attribute/BabelStorage.php` — duplicate `enum StorageMode` removed.
- `bu/command-bundle/`:
  - Vendored-from-zenstruck attribution corrected in `src/CommandRunner.php` (preserves Kevin Bond's MIT credit, drops the misleading "part of the zenstruck/console-extra package" claim).
  - `config/routes.yaml` deleted (dead — `HasConfigurableRoutes` registers via compiler pass).
  - `README.md` rewritten: dropped 6.4/7 setup recipes, added Symfony 8.0+/PHP 8.4+ requirements block, replaced zenstruck-flavored "Using Invokable Commands" section with a Symfony 8.1 `#[AsCommand]`-on-method example.
- `.github/workflows/split.yml` — `lib/command-schema` registered in all three places (manual-trigger JSON, all-packages bash list, changed-package case statement). On next push to `main`, repo `survos/command-schema` will be auto-created and split into.
- `md/composer.json` and `tui-monitor/composer.json` — path repo for `../mono/lib/command-schema` + `survos/command-schema: @dev` require. Switch to packagist `^x.y` once first tag ships.

## Architecture decisions (locked)

- **One package now.** Schema DTOs + introspector + TUI all in `survos/command-schema`. Split out a separate TUI package only if web/MCP consumers complain about `symfony/tui` in their tree (won't, since it's `suggest`).
- **`vendor/bin/commands`** — generic name, accepts the rare collision risk. Dispatches between dump and TUI modes inside one bin.
- **Consumers as siblings, not children.** `command-schema` (lib) is shared metadata. `command-bundle` (web form, to be refactored), `command-mcp-bundle` (planned), and the `command-schema` TUI itself all consume the schema. command-bundle is **not** deprecated; it stays the web-form surface and gets refactored to consume the lib instead of doing its own ad-hoc parsing.
- **No code sharing with tui-monitor for now.** Same widget shapes, very different state models (live processes vs static schemas). User OK with revisiting share-code once both are mature; not a hard "no" anymore.
- **Class name `Palette` is placeholder.** Naming options on the table:
  - `Palette` — VS Code metaphor, my pick if scope stays "commands."
  - `Explorer` — better if this grows into the unified TUI (processes / log-channels / commands).
  - `Catalog` — conservative third.
- **`#[Ask]` / `#[AskChoice]` injection collapses Phase 4 (interactive prompts).** The hard problem (intercept synchronous STDIN while TUI owns the terminal) goes away because attribute-driven prompts are read by reflection *before* invocation. Same pipeline that handles `#[Argument]` / `#[Option]` will handle them.

## Open questions / decisions deferred

- Final name (Palette / Explorer / Catalog).
- Whether to fold tui-monitor's "watch managed processes" + a future "watch Monolog channels" + this palette into one unified TUI bin. User's instinct: separate for now, revisit when there's a real shared widget (not just shared layout). Monolog-channel watcher idea: lives in tui-monitor's space if it happens, transport via per-channel log file + tail is the simplest first cut.
- MCP exposure rules locked but not built. Summary: `#[AsTool]` opt-in atop `#[AsCommand]`, always `--no-interaction` (transport invariant), no async/Messenger dispatch, schema-level arg constraints (min/max/enum) enforce the speed contract, destructiveness flag separate from speed. Build after structured output contract.
- Structured output contract design (next big design topic if you want to push MCP forward): typed return values mirror `#[MapInput]`, reflection-based generic renderer for `--format=human`, `Symfony\Serializer` for `--format=json`. Skipped for now in favor of TUI work.

## Next phases for the TUI (in order)

Each phase ships value at the end. Phases 1–3 are mechanical; phase 4 became cheap.

1. ✅ **Skeleton** — sidebar + help pane + quit. **Done this session.**
2. **Non-interactive run.** Add a "run" key. Execute via `CommandRunner` (already vendored from zenstruck/console-extra in command-bundle; possibly move into command-schema or duplicate). Capture output to `BufferedOutput`, render in result pane.
3. **Args form.** Walk `getDefinition()`, render one input row per argument/option. Submit assembles input array, runs.
4. **Interactive prompts.** Read `#[Ask]` / `#[AskChoice]` parameter attributes via reflection; render as form fields. Free once 8.1's attribute classes are stable enough to depend on directly. **PromptSchema is stubbed in v1 — its population is what unblocks this.**
5. **Profile pass-through.** Toggle for `--profile`; OSC 8 hyperlink in result pane after completion.

## To pick back up

1. Pull on the other machine; run the TUI: `cd ~/sites/tui-monitor && vendor/bin/commands`. Confirm sidebar/help pane render correctly.
2. If TUI has visual issues, debug against patterns in `tui-monitor/lib/SurvosSupervisorBundle/src/Tui/Dashboard.php` (the established symfony/tui usage in this codebase).
3. Decide naming (Palette / Explorer / Catalog), rename `src/Tui/Palette.php` if needed.
4. Pick the next phase. Probably phase 2 (non-interactive run) — smallest UX gain that makes the tool actually useful, not just informational.

## Untouched but worth remembering

- `survos/command-schema` will appear on Packagist after the next push to `main` triggers `split.yml`. Until then, path repo is the only way to consume it.
- The babel-bundle fix needs a re-tag if any non-symlinked consumer (one that pulls from packagist, not local source) wants the fix. md and tui-monitor use the symlinked monorepo source so they got it for free.
- command-bundle's web form still does its own ad-hoc command introspection; refactoring it to consume command-schema is on the roadmap but not urgent.

## File inventory (this session's deltas)

```
mono/
├── lib/command-schema/                                   (new package)
│   ├── composer.json
│   ├── README.md
│   ├── .gitignore
│   ├── phpunit.xml.dist
│   ├── SESSION.md                                        (this file)
│   ├── bin/commands                                      (executable)
│   ├── src/
│   │   ├── Schema/{CommandSchema,ArgumentSchema,OptionSchema,PromptSchema}.php
│   │   ├── Introspector/CommandIntrospector.php
│   │   └── Tui/Palette.php
│   └── tests/{bootstrap.php,IntrospectorTest.php,Fixtures/MultiCommand.php}
│
├── bu/babel-bundle/src/Attribute/
│   ├── StorageMode.php                                   (extracted)
│   └── BabelStorage.php                                  (enum removed)
│
├── bu/command-bundle/
│   ├── src/CommandRunner.php                             (attribution corrected)
│   ├── config/routes.yaml                                (deleted)
│   └── README.md                                         (rewritten)
│
└── .github/workflows/split.yml                           (command-schema registered)

md/composer.json                                          (path repo + require)
tui-monitor/composer.json                                 (path repo + require)
```
