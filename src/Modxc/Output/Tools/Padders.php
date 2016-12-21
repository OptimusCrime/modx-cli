<?php
namespace Modxc\Output\Tools;

use Modxc\Output\Alignment;

class Padders
{
    public static function leftPad($str, $length)
    {
        return str_pad($str, $length, ' ', STR_PAD_RIGHT);
    }

    public static function rightPad($str, $length)
    {
        return str_pad($str, $length, ' ', STR_PAD_LEFT);
    }

    public static function pad($direction, $str, $length)
    {
        if ($direction === Alignment::LEFT) {
            return Padders::leftPad($str, $length);
        }

        return Padders::rightPad($str, $length);
    }
}
