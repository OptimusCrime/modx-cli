<?php
namespace Modxc\Command;

use Modxc\Output\Alignment;
use Modxc\Output\Table\Row;
use Modxc\Output\Table\RowSeparator;
use Modxc\Output\Table\TableContainer;
use Modxc\Output\Handlers\TableHandler;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageSearchCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('package:search')
            ->setDescription('Search for a MODX package')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Package name'
            )
            ->addOption(
                'overwrite',
                'o',
                InputOption::VALUE_NONE,
                'When specified, a backup with the same name will be overwritten if it exists.'
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $response = $this->runProcessor('workspace/packages/rest/getlist', [
            'provider' => 1,
            'query' => 'tiny',
        ]);

        if ($response === null) {
            return;
        }

        $index = 0;
        $table = new TableContainer(2);
        $rowAlignment = [Alignment::LEFT, Alignment::RIGHT];
        foreach ($response['results'] as $package) {
            $rowContent = [
                $package['name'] . ' :: ' . $package['version'],
                '[' . $index . ']'
            ];

            $table->addRow(new Row($rowContent, $rowAlignment));
            $table->addRow(new RowSeparator());

            $index++;
        }

        $output->write(PHP_EOL);
        $output->write($table->output(new TableHandler()));
        $output->write(PHP_EOL);
        $output->writeln('<info>- You can use package:install [index-number] to install any package returned by the '
            . 'search.</info>');
        $output->writeln('<info>- You can use package:details [index-number] to display information about any package '
            . 'returned by the search.</info>');
    }
}
