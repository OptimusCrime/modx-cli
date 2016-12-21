<?php
namespace Modxc\Output\Handlers;

use Modxc\Output\ContainerInterface;
use Modxc\Output\Table\Row;
use Modxc\Output\Table\RowSeparator;
use Modxc\Output\Tools\Padders;

class TableHandler implements HandlerInterface
{
    const COLUMN_SPACING = 2;

    private $input;
    private $inputRaw;
    private $output;
    private $widestColumns;
    private $tableWidth;

    public function __construct()
    {
        $this->input = null;
        $this->inputRaw = [];
        $this->output = null;
        $this->widestColumns = [];
        $this->tableWidth = 0;
    }

    public function setInput(ContainerInterface $input)
    {
        $this->input = $input;
    }

    public function getOutput()
    {
        if ($this->output === null) {
            $this->createOutput();
        }

        return $this->output;
    }

    private function createOutput()
    {
        $this->calculateWidths();
        $this->calculateTableWidth();
        $this->createRawOutput();
        $this->transformRawOutput();
    }

    private function calculateWidths()
    {
        $this->widestColumns = array_fill(0, $this->input->getWidth(), 0);
        foreach ($this->input->getRows() as $row) {
            if (!($row instanceof RowSeparator)) {
                $this->handleColumns($row->getColumns());
            }
        }
    }

    private function handleColumns(array $columns)
    {
        for ($i = 0; $i < count($columns); $i++) {
            $columnLength = strlen($columns[$i]);
            if ($columnLength > $this->widestColumns[$i]) {
                $this->widestColumns[$i] = $columnLength;
            }
        }
    }

    private function calculateTableWidth()
    {
        // The final width of the table depends on the widest content in each column
        $this->tableWidth = array_sum($this->widestColumns);

        // We also add extra two spaces for each column (to make it look prettier)
        $this->tableWidth += count($this->widestColumns) * TableHandler::COLUMN_SPACING;
        foreach ($this->widestColumns as &$column) {
            $column += TableHandler::COLUMN_SPACING;
        }

        // Finally we add the width of the separator | between the columns
        $this->tableWidth += count($this->widestColumns) - 1;
    }

    private function createRawOutput()
    {
        foreach ($this->input->getRows() as $row) {
            if ($row instanceof RowSeparator) {
                $this->inputRaw[] = [$row->getSeparator($this->tableWidth)];
                continue;
            }

            $this->inputRaw[] = $this->createRawRowOutput($row);
        }
    }

    private function createRawRowOutput(Row $row)
    {
        $output = [];
        for ($i = 0; $i < count($row->getColumns()); $i++) {
            $length = $this->widestColumns[$i];
            $content = $row->getColumn($i);
            $direction = $row->getAlignment($i);

            $output[] = Padders::pad($direction, $content, $length);
        }

        return $output;
    }

    private function transformRawOutput()
    {
        foreach ($this->inputRaw as $row) {
            $this->output .= implode('|', $row) . PHP_EOL;
        }
    }
}
