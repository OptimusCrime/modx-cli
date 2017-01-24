<?php
namespace Modxc\Commands\Package;

use Modxc\Wrapper;
use Modxc\Commands\BaseCommand;

use Alchemy\Zippy\Zippy;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommand extends BaseCommand
{
    // Welcome to world championship in terrible regex patterns. We want to match (and capture) repository links.
    // Example patterns:
    // - https://github.com/OptimusCrime/modx-staticcollector?tag=1.1.1
    // - https://github.com/OptimusCrime/modx-staticcollector?release=1.0.0-pl
    // - https://github.com/OptimusCrime/modx-staticcollector?commit=cad3b0ceec3e03d13a98bf04bd8d995154fe6a21
    // - https://github.com/OptimusCrime/modx-staticcollector?branch=master
    // - http://github.com/OptimusCrime/modx-staticcollector
    // - github.com/derp/derp
    // - git@github.com:OptimusCrime/modx-staticcollector.git
    // - https://github.com/OptimusCrime/modx-staticcollector.git

    const GITHUB_REPOSITORY_PACKAGE =  "/^"

        // Optional http/https protocol (supporting both http patterns and git patterns)
        . "(?:https?)?"

        // Match github.com with leading characters (like slashes and colon after http/https)
        . ".*github\.com"

        // Match either a slash / (https pattern) or a colon (git pattern)
        . "(?:(?:\/)|:)"

        // Capture the repository owner
        . "(?P<owner>[a-zA-Z0-9-_]*)"

        . "\/"

        // Capture repository name
        . "(?P<repo>[a-zA-Z0-9-_]*)"

        // Optionally match .git (if git pattern)
        . "(?:\.git)?"

        // TODO implement system to support tag/commit/whatever
        . "\\??(?P<fetch>.*)?"
        . "/";

    private $zipName;
    private $modxBaseDirContents;
    private $buildPackageLocation;
    private $buildVariable;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->zipName = null;
        $this->buildPackageLocation = null;
        $this->modxBaseDirContents = [];
    }

    protected function configure()
    {
        $this
            ->setName('package:install')
            ->setDescription('Install a MODX package')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Either index value returned by a previous search or a download link to a GitHub repository'
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputInterface->write(PHP_EOL);

        if (is_numeric($this->inputInterface->getArgument('input'))) {
            $this->executeIndexDownload();
            return null;
        }

        if (self::isRepositoryDownload($this->inputInterface->getArgument('input'))) {
            $this->executeRepositoryDownload();
            return null;
        }

        $this->outputInterface->writeln('<comment>Packages can only be downloaded by specifying a index</comment>');
        $this->outputInterface->writeln('<comment>from a search. Reason for this is that repositories only</comment>');
        $this->outputInterface->writeln('<comment>allow search by display name, but require signatures</comment>');
        $this->outputInterface->writeln('<comment>to download.</comment>');
    }

    private function executeIndexDownload()
    {
        $cache = Wrapper::getInstance()->getCache();
        $package = $this->findPackageInCache($this->inputInterface->getArgument('input'), $cache);

        if ($package === null) {
            $this->outputInterface->writeln('<error>Could not find any cached results with that index.</error>');
            return null;
        }

        $this->outputInterface->writeln('<info>Attempting to download and install package ' . $package['name']);

        $this->processPackage($package['signature'], $package['provider']);
    }

    private function executeRepositoryDownload()
    {
        if (!$this->prepareRepositoryPackage()) {
            return null;
        }

        if (!$this->buildRepositoryPackage()) {
            $this->cleanupRepositoryPackage();
            return null;
        }

        $this->installRepositoryPackage();
    }

    private function prepareRepositoryPackage()
    {
        $matches = self::getRepositoryData($this->inputInterface->getArgument('input'));
        $downloaded = $this->downloadRepositoryPackage($matches['owner'][0], $matches['repo'][0], $matches['fetch'][0]);

        $this->modxBaseDirContents = self::getModxBaseDirFiles(
            Wrapper::getInstance()->getModx()->getOption('base_path')
        );

        if (!$downloaded) {
            $this->outputInterface->writeln('<error>Failed to download package.</error>');
            $this->cleanupRepositoryPackage();
            return false;
        }

        $this->outputInterface->write(PHP_EOL);

        $unpackage = $this->unpackageRepositoryPackageFile();
        if (!$unpackage) {
            $this->outputInterface->writeln('<error>Failed to unzip package.</error>');
            $this->cleanupRepositoryPackage();
            return false;
        }

        $this->outputInterface->write(PHP_EOL);

        $adjust = $this->adjustRepositoryPackageContent();
        if (!$adjust) {
            $this->outputInterface->writeln('<error>Failed to adjust package content.</error>');
            $this->cleanupRepositoryPackage();
            return false;
        }

        $this->outputInterface->write(PHP_EOL);

        return true;
    }

    private function buildRepositoryPackage()
    {
        $this->outputInterface->writeln('<comment>Attempting to build package.</comment>');

        $packageTransportFile = self::findPackageBaseDir($this->zipName) . '_build/build.transport.php';

        include_once $packageTransportFile;

        if ($this->buildVariable !== null) {
            $this->buildPackageLocation = @(${$this->buildVariable}->directory) . @(${$this->buildVariable}->signature);
        }

        $this->outputInterface->writeln('<info>Package successfully built.</info>');
        $this->outputInterface->write(PHP_EOL);

        return true;
    }

    private function installRepositoryPackage()
    {
        $this->outputInterface->writeln('<comment>Attempting to install package</comment>');
        $modxInstance = Wrapper::getInstance()->getModx();

        if ($this->buildPackageLocation === null) {
            $packageFile = $this->searchRepositoryPackage();
        }
        else {
            $packageFile = $this->buildPackageLocation . '.transport.zip';
        }

        $packageNameSplit = explode('/', $packageFile);
        $packageNameClean = $packageNameSplit[count($packageNameSplit) - 1];
        $packageDestination = $modxInstance->getOption('core_path') . 'packages/' . $packageNameClean;
        copy($packageFile, $packageDestination);

        $this->outputInterface->writeln('<comment>Copying file from: ' . $packageFile . '</comment>');
        $this->outputInterface->writeln('<comment>to: ' . $packageDestination . '</comment>');

        $response = $this->runProcessor('workspace/packages/scanlocal');

        if ($response === null) {
            $this->outputInterface->writeln('<error>Failed to scan and locate package.</error>');
            return false;
        }

        $this->installPackage(str_replace('.transport.zip', '', $packageNameClean));

        $this->cleanupRepositoryPackage();
    }

    private function searchRepositoryPackage()
    {
        $modxInstance = Wrapper::getInstance()->getModx();
        $currentModxFiles = self::getModxBaseDirFiles(
            $modxInstance->getOption('base_path')
        );

        $packageFile = self::findArrayMissing($this->modxBaseDirContents, $currentModxFiles);
        if ($packageFile === null) {
            $this->outputInterface->writeln('<error>Failed to locate built package.</error>');
            return false;
        }
    }

    private function downloadRepositoryPackage($owner, $name, $fetch)
    {
        $this->zipName = self::generateTempZipName();
        $repositoryUrl = self::buildGitHubDownloadLink($owner, $name, $fetch);

        if ($repositoryUrl === null) {
            return null;
        }

        $targetDir = Wrapper::getInstance()->getCacheDir() . $this->zipName . '.zip';

        $this->outputInterface->writeln('<comment>Downloading package from ' . $repositoryUrl . '</comment>');
        $this->outputInterface->writeln('<comment>Target location: ' . $targetDir . '</comment>');

        $file = fopen($targetDir, 'w+');

        $curlResource = curl_init($repositoryUrl);
        curl_setopt($curlResource, CURLOPT_FILE, $file);
        curl_setopt($curlResource, CURLOPT_TIMEOUT, 5040);
        curl_setopt($curlResource, CURLOPT_FOLLOWLOCATION, 1);

        curl_exec($curlResource);
        curl_close($curlResource);
        fclose($file);

        $this->outputInterface->writeln('<info>Successfully downloaded repository zip.</info>');

        return true;
    }

    private static function buildGitHubDownloadLink($owner, $name, $fetch)
    {
        if (strlen($fetch) === 0 or $fetch == '' or $fetch == null) {
            return self::buildGitHubFetchDownloadLink($owner, $name, 'master');
        }

        if (strpos($fetch, '=') === false) {
            return null;
        }

        $fetchSplit = explode('=', $fetch);
        if (!in_array($fetchSplit[0], ['tag', 'release', 'branch', 'commit']) or strlen($fetchSplit[1]) === 0) {
            return null;
        }

        return self::buildGitHubFetchDownloadLink($owner, $name, $fetchSplit[1]);
    }

    private static function buildGitHubFetchDownloadLink($owner, $name, $value)
    {
        return 'https://github.com/' . $owner . '/' . $name . '/archive/' . $value . '.zip';
    }

    private function unpackageRepositoryPackageFile()
    {
        $this->outputInterface->writeln('<comment>Attempting to unzip package.</comment>');

        $zipFilePath = Wrapper::getInstance()->getCacheDir() . $this->zipName . '.zip';
        $zippy = Zippy::load();
        $archive = $zippy->open($zipFilePath);

        @mkdir(Wrapper::getInstance()->getCacheDir() . $this->zipName);

        $archive->extract(Wrapper::getInstance()->getCacheDir() . $this->zipName);

        $this->outputInterface->writeln('<info>Successfully unzipped package.</info>');

        return true;
    }

    private function adjustRepositoryPackageContent()
    {
        $this->outputInterface->writeln('<comment>Attempting to adjust package.</comment>');

        // We need to make some adjustments to the package we have downloaded. We also need to validate that the
        // package has the format we expect and the files that we need
        $baseDirectory = self::findPackageBaseDir($this->zipName);
        if ($baseDirectory === null) {
            return false;
        }

        // Make sure we have a _build directory
        if (!is_dir($baseDirectory . '_build')) {
            return false;
        }

        // Make sure we have a build.transport.php file (support for config.json needs to be added later)
        $buildTransportFile = $baseDirectory . '_build/build.transport.php';
        if (!file_exists($buildTransportFile)) {
            return false;
        }

        return $this->cleanTransportFile($buildTransportFile);
    }

    private function cleanTransportFile($file)
    {
        $transportFileData = file($file);
        $transportAdjusted = [];
        foreach ($transportFileData as $line) {
            if (strpos($line, 'build.config.php') !== false
                or strpos($line, 'modx.class.php') !== false
                or self::killsScript($line)) {
                continue;
            }

            $this->findBuilderVariable($line);


            $transportAdjusted[] = $line;
        }

        file_put_contents($file, implode(PHP_EOL, $transportAdjusted));

        $this->outputInterface->writeln('<info>Successfully adjusted package.</info>');

        return true;
    }

    private function findBuilderVariable($line)
    {
        preg_match_all('/\$(?P<variable>(?!\W).*)\w?=(?:.*)modPackageBuilder\w?\(/', $line, $matches);
        if (isset($matches['variable'][0]) and strlen($matches['variable'][0]) > 0) {
            $cleanVariable = preg_replace('/\s+/', '', $matches['variable'][0]);
            if (strlen($cleanVariable) > 0) {
                $this->buildVariable = preg_replace('/\s+/', '', $matches['variable'][0]);
            }
        }
    }

    private static function killsScript($line)
    {
        $lineClean = preg_replace('/\s+/', '', $line);
        return strpos($lineClean, 'exit(') !== false
            or strpos($lineClean, 'exit;') !== false
            or strpos($lineClean, 'die(') !== false
            or strpos($lineClean, 'die;') !== false;
    }

    private function cleanupRepositoryPackage()
    {
        $this->outputInterface->writeln('<info>Cleaning up</info>');

        $cacheDir = Wrapper::getInstance()->getCacheDir();
        $filesystem = new Filesystem();

        // Remove zip file
        $zipFile = $cacheDir . $this->zipName . '.zip';
        if ($filesystem->exists($zipFile)) {
            $filesystem->remove($zipFile);
        }

        // Remove extracted directory
        $extractedDirectory = $cacheDir . $this->zipName;
        if ($filesystem->exists($extractedDirectory)) {
            $filesystem->remove($extractedDirectory);
        }
    }

    private function processPackage($signature, $provider)
    {
        $packageInformation = $this->downloadPackage($signature, $provider);
        if ($packageInformation === null) {
            $this->outputInterface->writeln('<error>Failed to download.</error>');
            return null;
        }

        $this->installPackage($packageInformation['object']['signature']);
    }

    private function downloadPackage($signature, $provider)
    {
        $response = $this->runProcessor('workspace/packages/rest/download', [
            'info' => $signature,
            'provider' => $provider,
        ]);

        if ($response === null) {
            return null;
        }

        $this->outputInterface->writeln('<info>Successfully downloaded.</info>');

        return $response;
    }

    private function installPackage($signature)
    {
        $response = $this->runProcessor('workspace/packages/install', [
            'signature' => $signature
        ]);

        if ($response === null) {
            return null;
        }

        $this->outputInterface->writeln('<info>Successfully installed.</info>');
    }

    private function findPackageInCache($identifier, $cache)
    {
        foreach ($cache as $package) {
            if ($package['index'] == $identifier) {
                return $package;
            }
        }

        return null;
    }

    private static function isRepositoryDownload($input)
    {
        $matches = self::getRepositoryData($input);
        return isset($matches['owner'][0])
        and strlen($matches['owner'][0]) > 0
        and isset($matches['repo'][0])
        and strlen($matches['repo'][0]) > 0;
    }

    private static function getRepositoryData($input)
    {
        preg_match_all(self::GITHUB_REPOSITORY_PACKAGE, $input, $matches);
        return $matches;
    }

    private static function generateTempZipName($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private static function findPackageBaseDir($name)
    {
        $base = Wrapper::getInstance()->getCacheDir() . $name . '/';
        $dirs = array_filter(glob($base . '*'), 'is_dir');
        if (count($dirs) == 1) {
            return $dirs[0] . '/';
        }

        return null;
    }

    private static function getModxBaseDirFiles($path)
    {
        return array_filter(glob($path . '*'), 'is_file');
    }

    private static function findArrayMissing($arrOriginal, $arrNew)
    {
        foreach ($arrNew as $element) {
            if (!in_array($element, $arrOriginal)) {
                return $element;
            }
        }

        return null;
    }
}
