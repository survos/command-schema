<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Tui;

use Survos\CommandSchema\Schema\CommandSchema;
use Symfony\Component\Tui\Event\InputEvent;
use Symfony\Component\Tui\Event\SelectionChangeEvent;
use Symfony\Component\Tui\Input\Key;
use Symfony\Component\Tui\Style\Border;
use Symfony\Component\Tui\Style\BorderPattern;
use Symfony\Component\Tui\Style\Direction;
use Symfony\Component\Tui\Style\Style;
use Symfony\Component\Tui\Style\StyleSheet;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\ContainerWidget;
use Symfony\Component\Tui\Widget\SelectListWidget;
use Symfony\Component\Tui\Widget\TextWidget;

/**
 * Sidebar-of-commands TUI. v1: browse + read help. Run/args-form/interactive
 * prompts come in later phases.
 */
final class Palette
{
    private Tui $tui;
    private SelectListWidget $sidebar;
    private TextWidget $details;
    private TextWidget $header;
    private TextWidget $footer;

    /** @var array<string, CommandSchema> */
    private array $schemasByName;

    /**
     * @param CommandSchema[] $schemas
     */
    public function __construct(array $schemas)
    {
        $byName = [];
        foreach ($schemas as $schema) {
            $byName[$schema->name] = $schema;
        }
        ksort($byName);
        $this->schemasByName = $byName;

        $this->tui = new Tui($this->buildStyleSheet());
        $this->sidebar = new SelectListWidget($this->buildSidebarItems(), maxVisible: 30);
        $this->details = new TextWidget('');
        $this->details->addStyleClass('details');
        $this->header = new TextWidget(\sprintf(' Survos Commands  ·  %d total', \count($byName)));
        $this->header->addStyleClass('chrome');
        $this->footer = new TextWidget(' ↑↓ select  ·  Tab focus  ·  q quit');
        $this->footer->addStyleClass('chrome');
    }

    public function run(): int
    {
        $body = new ContainerWidget();
        $body->addStyleClass('body');
        $body->add($this->sidebar);
        $body->add($this->details);

        $this->tui->add($this->header);
        $this->tui->add($body);
        $this->tui->add($this->footer);
        $this->tui->setFocus($this->sidebar);

        $this->sidebar->onSelectionChange(function (SelectionChangeEvent $event): void {
            $this->renderDetails((string) ($event->getValue() ?? ''));
        });

        $this->tui->addListener(function (InputEvent $event): void {
            $key = $event->getData();
            $consumed = match (true) {
                $key === 'q', $key === Key::ctrl('c') => $this->doQuit(),
                $key === "\t" => $this->cycleFocus(),
                default => false,
            };
            if ($consumed) {
                $event->stopPropagation();
            }
        });

        $first = array_key_first($this->schemasByName);
        if ($first !== null) {
            $this->renderDetails($first);
        }

        $this->tui->run();

        return 0;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function buildSidebarItems(): array
    {
        $items = [];
        foreach ($this->schemasByName as $name => $_) {
            $items[] = ['value' => $name, 'label' => $name];
        }

        return $items;
    }

    private function renderDetails(string $name): void
    {
        $schema = $this->schemasByName[$name] ?? null;
        $this->details->setText($schema === null ? '' : $this->formatHelp($schema));
    }

    private function formatHelp(CommandSchema $schema): string
    {
        $lines = [];

        $title = "\e[1m{$schema->name}\e[0m";
        if ($schema->description !== null) {
            $title .= "  \e[2m—\e[0m  " . $schema->description;
        }
        $lines[] = $title;
        $lines[] = '';

        $usage = $schema->name;
        if ($schema->options !== []) {
            $usage .= ' [options]';
        }
        foreach ($schema->arguments as $arg) {
            $token = $arg->required ? "<{$arg->name}>" : "[<{$arg->name}>]";
            if ($arg->array) {
                $token .= '...';
            }
            $usage .= ' ' . $token;
        }
        $lines[] = "\e[2mUSAGE\e[0m";
        $lines[] = '  ' . $usage;
        $lines[] = '';

        if ($schema->arguments !== []) {
            $lines[] = "\e[2mARGUMENTS\e[0m";
            foreach ($schema->arguments as $arg) {
                $marker = $arg->required ? '*' : ' ';
                $lines[] = \sprintf('  %s %-22s  %s', $marker, $arg->name, $arg->description ?? '');
            }
            $lines[] = '';
        }

        if ($schema->options !== []) {
            $lines[] = "\e[2mOPTIONS\e[0m";
            foreach ($schema->options as $opt) {
                $flag = '--' . $opt->name;
                if ($opt->shortcut !== null && $opt->shortcut !== '') {
                    $flag = '-' . $opt->shortcut . ', ' . $flag;
                }
                if ($opt->valueRequired) {
                    $flag .= '=VALUE';
                } elseif ($opt->acceptValue) {
                    $flag .= '[=VALUE]';
                }
                $lines[] = \sprintf('  %-32s  %s', $flag, $opt->description ?? '');
            }
            $lines[] = '';
        }

        if ($schema->aliases !== []) {
            $lines[] = "\e[2mALIASES\e[0m  " . implode(', ', $schema->aliases);
            $lines[] = '';
        }

        if ($schema->help !== null && trim($schema->help) !== '') {
            $lines[] = "\e[2mHELP\e[0m";
            foreach (explode("\n", trim($schema->help)) as $line) {
                $lines[] = '  ' . $line;
            }
        }

        return implode("\n", $lines);
    }

    private function doQuit(): bool
    {
        $this->tui->stop();

        return true;
    }

    private function cycleFocus(): bool
    {
        $current = $this->tui->getFocus();
        $this->tui->setFocus($current === $this->sidebar ? $this->details : $this->sidebar);

        return true;
    }

    private function buildStyleSheet(): StyleSheet
    {
        return new StyleSheet([
            ':root' => new Style(direction: Direction::Vertical),
            '.body' => new Style(direction: Direction::Horizontal, gap: 1),
            '.chrome' => new Style(dim: true),
            '.details' => new Style(
                flex: 1,
                border: Border::from([1], BorderPattern::ROUNDED, 'gray'),
            ),
            '.details:focus' => new Style(
                border: Border::from([1], BorderPattern::ROUNDED, 'cyan'),
            ),
            SelectListWidget::class => new Style(
                maxColumns: 50,
                border: Border::from([1], BorderPattern::ROUNDED, 'gray'),
            ),
            SelectListWidget::class . ':focus' => new Style(
                border: Border::from([1], BorderPattern::ROUNDED, 'cyan'),
            ),
        ]);
    }
}
