<?php
namespace Modxc\Output\Table;

class RowSeparator
{
    public function getSeparator($width)
    {
        return str_repeat('-', $width);
    }
}
