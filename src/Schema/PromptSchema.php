<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Schema;

/**
 * Interaction metadata attached to an argument or option — sourced from
 * Symfony 8.1+ #[Ask] / #[AskChoice] parameter attributes when present.
 *
 * Empty in v1 introspection (the reflection walker for parameter attributes
 * lands once 8.1 is stable enough to depend on the attribute classes directly).
 */
final class PromptSchema
{
    /**
     * @param string[]|null $choices
     */
    public function __construct(
        public readonly string $label,
        public readonly ?string $help = null,
        public readonly ?array $choices = null,
        public readonly bool $multiSelect = false,
    ) {}
}
