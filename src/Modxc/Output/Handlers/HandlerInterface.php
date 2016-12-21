<?php
namespace Modxc\Output\Handlers;

use Modxc\Output\ContainerInterface;

interface HandlerInterface
{
    public function setInput(ContainerInterface $input);

    public function getOutput();
}
