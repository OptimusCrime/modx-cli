<?php
namespace Modxc\Output\Table;

use Modxc\Output\ContainerInterface;
use Modxc\Output\Handlers\HandlerInterface;

class TableContainer implements ContainerInterface
{
    private $width;
    private $rows;

    public function __construct($width = null)
    {
        $this->width = $width;
        $this->rows = [];
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function addRow($row)
    {
        $this->rows[] = $row;
    }

    public function output(HandlerInterface $handler)
    {
        $handler->setInput($this);
        return $handler->getOutput();
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function getRow($index)
    {
        if (!isset($this->rows[$index])) {
            return null;
        }

        return $this->rows[$index];
    }
}
