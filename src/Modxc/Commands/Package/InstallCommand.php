<?php
namespace Modxc\Commands\Package;

use Modxc\Wrapper;
use Modxc\Commands\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('package:install')
            ->setDescription('Install a MODX package')
            ->addArgument(
                'index',
                InputArgument::REQUIRED,
                'Index value returned by a previous search'
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputInterface->write(PHP_EOL);

        if (is_numeric($this->inputInterface->getArgument('index'))) {
            $this->executeIndexDownload();
            return null;
        }

        $this->outputInterface->writeln('<comment>Packages can only be downloaded by specifying a index</comment>');
        $this->outputInterface->writeln('<comment>from a search. Reason for this is that repositories only</comment>');
        $this->outputInterface->writeln('<comment>allow search by display name, but require signatures</comment>');
        $this->outputInterface->writeln('<comment>to download.</comment>');
    }

    private function executeIndexDownload()
    {
        $cache = Wrapper::getInstance()->getCache();
        $package = $this->findPackageInCache($this->inputInterface->getArgument('index'), $cache);

        if ($package === null) {
            $this->outputInterface->writeln('<error>Could not find any cached results with that index.</error>');
            return null;
        }

        $this->outputInterface->writeln('<info>Attempting to download and install package ' . $package['name']);

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

    private function findPackageInCache($identifier, $cache)
    {
        foreach ($cache as $package) {
            if ($package['index'] == $identifier) {
                return $package;
            }
        }

        return null;
    }
}
