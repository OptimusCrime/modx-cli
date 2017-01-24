<?php
namespace Modxc\Commands;

use Modxc\Wrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    protected $modx;
    protected $outputInterface;
    protected $inputInterface;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->outputInterface = $output;
        $this->inputInterface = $input;

        $modxPath = null;
        if ($input->hasOption('core_path')) {
            $modxPath = $input->getOption('core_path');
        }

        $this->defineModx($output, $modxPath);
    }

    protected function defineModx(OutputInterface $output, $path)
    {
        $this->modx = Wrapper::getInstance()->loadModx($output, $path);
    }

    protected function runProcessor($action, $options = [])
    {
        $processor = $this->modx->runProcessor($action, $options);
        if (gettype($processor) == 'string' or $processor->isError()) {
            $this->outputInterface->writeln('<error>Something went wrong</error>');
            return null;
        }

        if (gettype($processor->getResponse()) === 'string') {
            return json_decode($processor->getResponse(), true);
        }

        return $processor->getResponse();
    }
}
