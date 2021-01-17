<?php

// Use our phar alias path
if (empty(\Phar::running())) {
    throw new \Exception("File must run from PHAR.");
}

$autoloaderPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloaderPath)) {
    die("Could not find autoloader. Run 'composer install'.");
}
$classLoader = require $autoloaderPath;

$configFilePath = getenv('ROBO_CONFIG') ?: getenv('HOME') . '/.robo/robo.yml';
$runner = new class extends \Robo\Runner {
    protected function processRoboOptions($argv) {
        parent::processRoboOptions($argv);
        $this->dir = __DIR__;
    }
};
$runner
    ->setRelativePluginNamespace('Robo\Plugin')
    ->setSelfUpdateRepository('') // we do not support self-update here!
    ->setConfigurationFilename($configFilePath)
    ->setEnvConfigPrefix('ROBO')
    ->setClassLoader($classLoader);

$args = $_SERVER['argv'];

require_once 'RoboFile.php';
$statusCode = $runner->run($argv, null, null, 'RoboFile', $classLoader);
exit($statusCode);
