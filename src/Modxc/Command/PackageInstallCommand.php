<?php
namespace Modxc\Command;

use Modxc\Wrapper;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageInstallCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('package:install')
            ->setDescription('Install a MODX package')
            ->addArgument(
                'identifier',
                InputArgument::REQUIRED,
                'Package identifier (either name or index from a search)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(PHP_EOL);

        if (is_numeric($input->getArgument('identifier'))) {
            $this->executeIndexDownload($input, $output);
            return null;
        }

        return $this->executeSearchDownload($input, $output);
    }

    private function executeIndexDownload(InputInterface $input, OutputInterface $output)
    {
        $cache = Wrapper::getInstance()->getCache();
        $package = $this->findPackage($input->getArgument('identifier'), $cache);

        if ($package === null) {
            $output->writeln('<error>Could not find any cached results with that index.</error>');
            return null;
        }

        $output->writeln('<info>Attempting to download and install package ' . $package['name']);

        $this->processPackage($package['signature'], $package['provider']);
    }

    private function processPackage($signature, $provider)
    {
        $packageInformation = $this->downloadPackage($signature, $provider);
        if ($packageInformation === null) {
            $this->outputInterface->writeln('<error>Failed to download.</error>');
            return null;
        }

        $this->installPackage($packageInformation['object']['signature']);
    }

    private function downloadPackage($signature, $provider)
    {
        $response = $this->runProcessor('workspace/packages/rest/download', [
            'info' => $signature,
            'provider' => $provider,
        ]);

        if ($response === null) {
            return null;
        }

        $this->outputInterface->writeln('<info>Successfully downloaded.</info>');

        return $response;
    }

    private function installPackage($signature)
    {
        $response = $this->runProcessor('workspace/packages/install', [
            'signature' => $signature
        ]);

        if ($response === null) {
            return null;
        }

        $this->outputInterface->writeln('<info>Successfully installed.</info>');
    }

    private function findPackage($identifier, $cache)
    {
        foreach ($cache as $package) {
            if ($package['index'] == $identifier) {
                return $package;
            }
        }

        return null;
    }

    private function executeSearchDownload(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Downloading package xcc');
    }
}
