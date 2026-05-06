<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Tests;

use PHPUnit\Framework\TestCase;
use Survos\CommandSchema\Introspector\CommandIntrospector;
use Survos\CommandSchema\Schema\CommandSchema;
use Survos\CommandSchema\Tests\Fixtures\MultiCommand;
use Symfony\Component\Console\Application;

final class IntrospectorTest extends TestCase
{
    public function testDescribesCommand(): void
    {
        $app = new Application();
        $app->addCommand(new MultiCommand());

        $schemas = (new CommandIntrospector())->describeAll($app);

        self::assertArrayHasKey('app:multi', $schemas);
        $schema = $schemas['app:multi'];
        self::assertInstanceOf(CommandSchema::class, $schema);

        self::assertSame('app:multi', $schema->name);
        self::assertSame('A fixture command exercising the introspector', $schema->description);
        self::assertNotNull($schema->help);
        self::assertStringContainsString('Help text', $schema->help);
        self::assertSame(['app:m'], $schema->aliases);
        self::assertSame('app', $schema->namespace());
        self::assertFalse($schema->hidden);

        self::assertCount(2, $schema->arguments);
        [$target, $extras] = $schema->arguments;
        self::assertSame('target', $target->name);
        self::assertTrue($target->required);
        self::assertFalse($target->array);
        self::assertSame('extras', $extras->name);
        self::assertFalse($extras->required);
        self::assertTrue($extras->array);
        self::assertSame([], $extras->default);

        $optionsByName = [];
        foreach ($schema->options as $option) {
            $optionsByName[$option->name] = $option;
        }

        self::assertArrayHasKey('limit', $optionsByName);
        self::assertSame('l', $optionsByName['limit']->shortcut);
        self::assertTrue($optionsByName['limit']->valueRequired);
        self::assertSame(10, $optionsByName['limit']->default);

        self::assertArrayHasKey('dry-run', $optionsByName);
        self::assertTrue($optionsByName['dry-run']->negatable);
    }

    public function testSkipsAliasEntries(): void
    {
        $app = new Application();
        $app->addCommand(new MultiCommand());

        $schemas = (new CommandIntrospector())->describeAll($app);

        // Application::all() lists `app:m` alongside `app:multi`; the introspector
        // should canonicalize and only emit one schema per command.
        self::assertArrayNotHasKey('app:m', $schemas);
    }

    public function testIncludesBuiltInCommands(): void
    {
        // A bare Application registers `help` and `list` — the introspector should
        // pick those up too, with empty namespace strings.
        $schemas = (new CommandIntrospector())->describeAll(new Application());

        self::assertArrayHasKey('help', $schemas);
        self::assertArrayHasKey('list', $schemas);
        self::assertSame('', $schemas['help']->namespace());
    }
}
