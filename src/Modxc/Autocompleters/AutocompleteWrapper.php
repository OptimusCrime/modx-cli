<?php
namespace Modxc\Autocompleters;

class AutocompleteWrapper
{
    public static function register()
    {
        readline_completion_function(function ($input, $index) {
            var_dump($input);
            var_dump($index);
            return ['foo', 'bar'];
        });
    }
}
