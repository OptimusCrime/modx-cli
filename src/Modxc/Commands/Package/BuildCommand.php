<?php
namespace Modxc\Commands\Package;

use Modxc\Wrapper;
use Modxc\Commands\BaseCommand;

use Alchemy\Zippy\Zippy;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class BuildCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('package:build')
            ->setDescription('Build a MODX package')
            ->addOption(
                'package_path',
                'pp',
                InputOption::VALUE_OPTIONAL,
                'Root location for the package you want to build (absolute)',
                null
            )
            ->addOption(
                'core_path',
                'cp',
                InputOption::VALUE_OPTIONAL,
                'Location for the MODX core (absolute)',
                null
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
