<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Schema;

final class CommandSchema
{
    /**
     * @param ArgumentSchema[] $arguments
     * @param OptionSchema[]   $options
     * @param string[]         $aliases
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $help,
        public readonly array $arguments,
        public readonly array $options,
        public readonly array $aliases,
        public readonly bool $hidden,
    ) {}

    public function namespace(): string
    {
        $colon = strpos($this->name, ':');

        return $colon === false ? '' : substr($this->name, 0, $colon);
    }
}
