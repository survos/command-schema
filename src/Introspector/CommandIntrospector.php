<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Introspector;

use Survos\CommandSchema\Schema\ArgumentSchema;
use Survos\CommandSchema\Schema\CommandSchema;
use Survos\CommandSchema\Schema\OptionSchema;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class CommandIntrospector
{
    /**
     * @param  (callable(string $commandName, \Throwable $error): void)|null $onError
     *         Invoked when a command can't be introspected (typically a LazyCommand whose
     *         service constructor throws). The command is skipped; the rest continue.
     * @return CommandSchema[] keyed by command name
     */
    public function describeAll(Application $application, ?callable $onError = null): array
    {
        $schemas = [];
        foreach ($application->all() as $name => $command) {
            // Application::all() includes aliases as separate keys; skip non-canonical entries.
            if ($command->getName() !== $name) {
                continue;
            }
            try {
                $schemas[$name] = $this->describe($command);
            } catch (\Throwable $error) {
                if ($onError !== null) {
                    $onError($name, $error);
                }
            }
        }

        return $schemas;
    }

    public function describe(Command $command): CommandSchema
    {
        $definition = $command->getDefinition();

        $arguments = [];
        foreach ($definition->getArguments() as $argument) {
            $arguments[] = $this->describeArgument($argument);
        }

        $options = [];
        foreach ($definition->getOptions() as $option) {
            $options[] = $this->describeOption($option);
        }

        return new CommandSchema(
            name: (string) $command->getName(),
            description: $command->getDescription() ?: null,
            help: $command->getHelp() ?: null,
            arguments: $arguments,
            options: $options,
            aliases: $command->getAliases(),
            hidden: $command->isHidden(),
        );
    }

    private function describeArgument(InputArgument $argument): ArgumentSchema
    {
        return new ArgumentSchema(
            name: $argument->getName(),
            description: $argument->getDescription() ?: null,
            required: $argument->isRequired(),
            array: $argument->isArray(),
            default: $argument->getDefault(),
        );
    }

    private function describeOption(InputOption $option): OptionSchema
    {
        return new OptionSchema(
            name: $option->getName(),
            shortcut: $option->getShortcut(),
            description: $option->getDescription() ?: null,
            acceptValue: $option->acceptValue(),
            valueRequired: $option->isValueRequired(),
            array: $option->isArray(),
            negatable: $option->isNegatable(),
            default: $option->getDefault(),
        );
    }
}
