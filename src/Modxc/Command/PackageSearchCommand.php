<?php
namespace Modxc\Command;

use Modxc\Wrapper;
use Modxc\Output\Alignment;
use Modxc\Output\Table\Row;
use Modxc\Output\Table\RowSeparator;
use Modxc\Output\Table\TableContainer;
use Modxc\Output\Handlers\TableHandler;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO FIX THIS
        $provider = 1;

        $response = $this->runProcessor('workspace/packages/rest/getlist', [
            'provider' => $provider,
            'query' => $input->getArgument('name'),
        ]);

        if ($response === null) {
            return null;
        }

        $cacheContent = [];

        $index = 0;
        $table = new TableContainer(2);
        $rowAlignment = [Alignment::LEFT, Alignment::RIGHT];
        $headerContent = ['Package name', 'Index'];

        $table->addRow(new Row($headerContent, $rowAlignment));
        $table->addRow(new RowSeparator('='));

        foreach ($response['results'] as $package) {
            $rowContent = [
                $package['name'] . ' :: ' . $package['version'],
                '[' . $index . ']'
            ];

            $table->addRow(new Row($rowContent, $rowAlignment));
            $table->addRow(new RowSeparator('-'));

            $cacheContent[] = [
                'index' => $index,
                'name' => $package['name'] . '-' . $package['version-compiled'],
                'provider' => $provider,
                'signature' => $package['location'] . '::' . $package['signature']
            ];

            $index++;
        }

        Wrapper::getInstance()->storeCache($cacheContent);

        $this->outputInterface->write(PHP_EOL);
        $this->outputInterface->write($table->output(new TableHandler()));
        $this->outputInterface->write(PHP_EOL);

        $this->outputInterface->writeln('<info>- You can use package:install [index-number] to install any</info>');
        $this->outputInterface->writeln('<info>  package returned by the search.</info>');
        $this->outputInterface->writeln('<info>- You can use package:details [index-number] to display</info>');
        $this->outputInterface->writeln('<info>  information about any package returned by the search.</info>');
    }
}
