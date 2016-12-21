<?php
namespace Modxc;

use Modxc\Command\PackageInstallCommand;
use Modxc\Command\PackageSearchCommand;

class Wrapper
{
    private static $instance = null;

    private $modx;
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

        $this->cacheFile = dirname(dirname(dirname(__FILE__))) . '/cache/modxc.cache.json';
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

        $application = new Modxc('Modxc', '0.0.1');
        $application->add(new PackageInstallCommand());
        $application->add(new PackageSearchCommand);
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
