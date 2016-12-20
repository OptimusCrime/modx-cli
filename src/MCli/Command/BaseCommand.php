<?php
namespace MCli\Command;

use MCli\Wrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    public static $separatorIndicator = '|SEP|';
    private static $separatorIndicatorPattern = '/\|SEP\|/m';

    protected $modx;
    private $outputInterface;
    private $outputBuffer;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->outputBuffer = [];
        $this->outputInterface = $output;
        $this->modx = Wrapper::getInstance()->getModx();
    }

    protected function addOutput($line)
    {
        $this->outputBuffer[] = $line;
    }

    protected function runProcessor($action, $options)
    {
        $processor = $this->modx->runProcessor($action, $options);
        if (gettype($processor) == 'string' or $processor->isError()) {
            $this->outputInterface->writeln('<error>Something went wrong</error>');
            return null;
        }

        return json_decode($processor->getResponse(), true);
    }

    protected function writeOutput()
    {
        $longestLine = 0;
        foreach ($this->outputBuffer as $line) {
            if (strlen($line) > $longestLine) {
                $longestLine = strlen($line);
            }
        }

        $sepString = str_repeat('-', $longestLine);
        $outputString = implode(PHP_EOL, $this->outputBuffer);

        $this->outputInterface->writeln(preg_replace(
            self::$separatorIndicatorPattern,
            $sepString,
            $outputString
        ));
    }
}
