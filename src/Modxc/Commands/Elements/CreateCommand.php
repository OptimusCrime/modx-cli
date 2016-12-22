<?php
namespace Modxc\Commands\Elements;

use Modxc\Wrapper;
use Modxc\Commands\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CreateCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('element:install')
            ->setDescription('Install a MODX package')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name (e.g. MySnippet) or a path (e.g. Category/To/MySnippet). If category does not exist, they ' .
                'will be created. If argument is not provided, interaction session with autocomplete will be started.'
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        readline("Foobar: ");
    }
}
