<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Schema;

final class OptionSchema
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $shortcut,
        public readonly ?string $description,
        public readonly bool $acceptValue,
        public readonly bool $valueRequired,
        public readonly bool $array,
        public readonly bool $negatable,
        public readonly mixed $default = null,
        public readonly ?PromptSchema $prompt = null,
    ) {}
}
