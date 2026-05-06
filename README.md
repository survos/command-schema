# survos/command-schema

Reflection-based metadata for Symfony Console commands.

Given a `Symfony\Component\Console\Application`, the introspector produces a typed `CommandSchema` per command — name, description, arguments, options, prompts. Consumers render commands in non-CLI surfaces:

- **TUI runner** (this package, when `symfony/tui` is installed) — `vendor/bin/commands`
- **Web form** — `survos/command-bundle`
- **MCP server** — `survos/command-mcp-bundle` (planned)

The package is pure metadata + a reflection walker. No Symfony bundle, no DI wiring, no runtime dependency on `HttpKernel`.

## Requirements

- PHP 8.4+
- Symfony Console 8.1+ (for method-based `#[AsCommand]` and parameter-attribute prompts)

## Library usage

```php
use Survos\CommandSchema\Introspector\CommandIntrospector;

$schemas = (new CommandIntrospector())->describeAll($application);

foreach ($schemas as $schema) {
    echo $schema->name . ': ' . $schema->description . "\n";
    foreach ($schema->arguments as $arg) {
        echo "  arg: {$arg->name}" . ($arg->required ? ' (required)' : '') . "\n";
    }
}
```

`$application` is any `Symfony\Component\Console\Application`. From inside a console command, `$this->getApplication()` returns the live one wired with every registered command.

## `vendor/bin/commands`

Boots the host project's kernel via the standard `KERNEL_CLASS` / `APP_ENV` / `APP_DEBUG` env vars (the same convention `bin/console` uses) and prints a namespace-grouped summary of every command:

```bash
composer require survos/command-schema
vendor/bin/commands
```

Defaults to `App\Kernel` if `KERNEL_CLASS` is unset, which covers any Symfony Flex project out of the box.

v1 output is plain text (command name, description, argument/option counts, grouped by namespace). The TUI renderer that adds a sidebar, help pane, and run-with-args lands once `symfony/tui` ships stable; install it now (`composer require symfony/tui`) and the bin will switch to TUI mode automatically once that integration is in.

## Schema shape

```
CommandSchema
├─ name, description, help, aliases[], hidden
├─ namespace()                         — derived from name (`app:foo` → `app`)
├─ arguments: ArgumentSchema[]
│   └─ name, description, required, array, default, prompt?
└─ options: OptionSchema[]
    └─ name, shortcut, description, acceptValue, valueRequired, array, negatable, default, prompt?
```

`PromptSchema` (label, choices, multiSelect) is the slot for `#[Ask]` / `#[AskChoice]` parameter-attribute metadata. Population requires reflection on the underlying invokable/method and is wired in once Symfony 8.1's attribute classes are stable enough to depend on directly.
