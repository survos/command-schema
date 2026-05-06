<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Schema;

final class ArgumentSchema
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $required,
        public readonly bool $array,
        public readonly mixed $default = null,
        public readonly ?PromptSchema $prompt = null,
    ) {}
}
