<?php
namespace Modxc;

use Modxc\Autocompleters\AutocompleteWrapper;
use Modxc\Commands\Elements\CreateCommand;
use Modxc\Commands\Package\InstallCommand;
use Modxc\Commands\Package\SearchCommand;

class Wrapper
{
    private static $instance = null;

    private $modx;
    private $cacheDir;
    private $cacheFile;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Wrapper();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->modx = null;

        $this->cacheDir = dirname(dirname(dirname(__FILE__))) . '/cache/';
        $this->cacheFile = $this->cacheDir . 'modxc.cache.json';
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function run()
    {
        $this->loadModx();
        if ($this->modx === null) {
            die('Could not load MODX. Are you sure there exists a config.core.php file in this tree?');
        }

        AutocompleteWrapper::register();

        $application = new Modxc('Modxc', '0.0.1');
        $application->add(new CreateCommand());
        $application->add(new InstallCommand());
        $application->add(new SearchCommand);
        $application->run();
    }

    public function getModx()
    {
        return $this->modx;
    }

    public function storeCache(array $input)
    {
        file_put_contents($this->cacheFile, json_encode($input));
    }

    public function getCache()
    {
        if (!file_exists($this->cacheFile)) {
            return [];
        }

        return json_decode(file_get_contents($this->cacheFile), true);
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    private function loadModx()
    {
        $configFile = $this->locateConfigFile();
        if ($configFile === null) {
            return;
        }

        $this->loadFiles($configFile);
        $this->initModx();
    }

    private function locateConfigFile()
    {
        $currentPath = getcwd();
        while (true) {
            $configFilePath = $currentPath . '/config.core.php';
            if (file_exists($configFilePath) and is_readable($configFilePath)) {
                return $configFilePath;
            }

            $newPath = dirname($currentPath);
            if ($newPath === $currentPath) {
                return null;
            }

            $currentPath = $newPath;
        }
    }

    private function loadFiles($configFile)
    {
        include_once $configFile;

        if (defined('MODX_CORE_PATH')) {
            include_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        }
    }

    private function initModx()
    {
        if (!class_exists('\modX')) {
            return;
        }

        define('MODX_API_MODE', true);
        $this->modx = new \modX(MODX_CORE_PATH . 'config/');
        if (is_object($this->modx) and ($this->modx instanceof \modX)) {
            $this->modx->initialize('mgr');
            $this->modx->getService('error', 'error.modError', '', '');
            $this->modx->setLogTarget('ECHO');
        }
    }

    private function __clone()
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function __wakeup()
    {
    }
}
