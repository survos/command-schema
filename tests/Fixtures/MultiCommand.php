<?php
declare(strict_types=1);

namespace Survos\CommandSchema\Tests\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:multi', 'A fixture command exercising the introspector')]
final class MultiCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('Help text for the multi fixture.')
            ->setAliases(['app:m'])
            ->addArgument('target', InputArgument::REQUIRED, 'Target name')
            ->addArgument('extras', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Extra targets')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit', 10)
            ->addOption('dry-run', null, InputOption::VALUE_NEGATABLE, 'Dry run', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
