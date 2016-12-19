<?php
namespace MCli;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class MCli extends Application
{
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition([
            new InputArgument(
                'command',
                InputArgument::REQUIRED,
                'The command to execute'
            ),
            new InputOption(
                '--help',
                '-h',
                InputOption::VALUE_NONE,
                'Display this help message.'
            ),
            new InputOption(
                '--verbose',
                '-v|vv|vvv',
                InputOption::VALUE_NONE,
                'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug.'
            ),
            new InputOption(
                '--version',
                '-V',
                InputOption::VALUE_NONE,
                'Display the MCli version.'
            ),
        ]);
    }
}
