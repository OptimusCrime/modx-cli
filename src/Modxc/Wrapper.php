<?php
namespace Modxc;

use Modxc\Autocompleters\AutocompleteWrapper;
use Modxc\Commands\Elements\CreateCommand;
use Modxc\Commands\Package\BuildCommand;
use Modxc\Commands\Package\InstallCommand;
use Modxc\Commands\Package\SearchCommand;

use Symfony\Component\Console\Output\OutputInterface;

class Wrapper
{
    private static $instance = null;

    private $application;
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
        AutocompleteWrapper::register();

        $this->application = new Modxc('Modxc', '0.0.1');
        $this->addCommands();
        $this->application->run();
    }

    private function addCommands()
    {
        // Elements
        $this->application->add(new CreateCommand());

        // Packages
        $this->application->add(new BuildCommand());
        $this->application->add(new InstallCommand());
        $this->application->add(new SearchCommand());
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

    public function loadModx(OutputInterface $output, $path = null)
    {
        $this->loadModxFiles($path);

        if ($this->modx === null) {
            $output->write(PHP_EOL);
            $output->writeln('<error>Could not load MODX.</error>');
            $output->write(PHP_EOL);
            $output->writeln('<comment>If you did not provide a MODX core path for this command, are you</comment>');
            $output->writeln('<comment>sure there exists a MODX installation in your current working</comment>');
            $output->writeln('<comment>directory tree?</comment>');

            die();
        }

        return $this->modx;
    }

    private function loadModxFiles($path = null)
    {
        if ($path === null) {
            $this->loadModxInWorkingDirectory();
            return;
        }

        $this->loadModxFromCorePath($path);
    }

    private function loadModxInWorkingDirectory()
    {
        $configFile = $this->locateConfigFile();
        if ($configFile === null) {
            return;
        }

        $this->loadFiles($configFile);
        $this->initModx();
    }

    private function loadModxFromCorePath($configFilePath)
    {
        if (substr($configFilePath, -1) !== '/') {
            $configFilePath .= '/';
        }

        $configFilePath .= 'config/config.inc.php';

        if ($this->validateConfigFile($configFilePath)) {
            $this->loadFiles($configFilePath);
            $this->initModx();
        }
    }

    private function locateConfigFile()
    {
        $currentPath = getcwd();
        while (true) {
            $configFilePath = $currentPath . '/config.core.php';
            if ($this->validateConfigFile($configFilePath)) {
                return $configFilePath;
            }

            $newPath = dirname($currentPath);
            if ($newPath === $currentPath) {
                return null;
            }

            $currentPath = $newPath;
        }
    }

    private function validateConfigFile($configFile)
    {
        if (file_exists($configFile) and is_readable($configFile)) {
            return true;
        }

        return false;
    }

    private function loadFiles($configFile)
    {
        if (!defined('MODX_CORE_PATH')) {
            include_once $configFile;
        }

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
