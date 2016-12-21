<?php
namespace Modxc\Output\Table;

class Row
{
    private $columns;
    private $alignment;

    public function __construct($data = [], $alignment = [])
    {
        $this->columns = $data;
        $this->alignment = $alignment;
    }

    public function addColumn($data)
    {
        $this->columns[] = $data;
    }

    public function addAllColumns(array $columns)
    {
        $this->columns = $columns;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getColumn($index)
    {
        if (!isset($this->columns[$index])) {
            return null;
        }

        return $this->columns[$index];
    }

    public function getAlignment($index)
    {
        if (!isset($this->alignment[$index])) {
            return null;
        }

        return $this->alignment[$index];
    }
}
