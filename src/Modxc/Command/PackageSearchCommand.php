<?php
namespace Modxc\Command;

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
        foreach ($response['results'] as $package) {
            $this->addOutput($package['name'] . ' :: ' . $package['version'] . ' [' . $index . ']');
            $this->addOutput(BaseCommand::$separatorIndicator);

            $index++;
        }

        $this->writeOutput();

        $output->write(PHP_EOL);
        $output->writeln('<info>You can now use package:install [index-number] to install the package returned by the 
                         search.</info>');
    }
}
