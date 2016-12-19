<?php
namespace MCli\Command;

use MCli\Wrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    protected $modx;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->modx = Wrapper::getInstance()->getModx();
    }
}
