<?php
namespace Modxc\Output\Table;

class RowSeparator
{
    private $char;

    public function __construct($char = '-')
    {
        $this->char = $char;
    }

    public function getSeparator($width)
    {
        return str_repeat($this->char, $width);
    }
}
