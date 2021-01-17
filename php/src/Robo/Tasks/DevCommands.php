<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Utility\Config;
use Robo\Collection\CollectionBuilder;

trait DevCommands {
    function devUp()
    {
        $config = new Config();
        $builderConfig = $config->getExtraConfig('saitho-cli');
        $devConfig = $builderConfig['dev'] ?? [];

        $builder = $this->getBuilder();
        /** @var $builder \RoboFile|CollectionBuilder */
        $builder
            ->taskComposerInstall()
                ->ignorePlatformRequirements()
            ->taskDdevStart();

        if (!empty($devConfig['sync-db'])) {
            $builder->taskDdevImportDb()
                ->src($devConfig['sync-db']);
        }
        if (!empty($devConfig['mirror-dirs']) && is_array($devConfig['mirror-dirs'])) {
            $builder->taskMirrorDir($devConfig['mirror-dirs']);
        }

        $this->_buildAssets($builder);

        $execUpTasks = $devConfig['exec']['up'] ?? [];
        foreach ($execUpTasks as $task) {
            $builder
                ->taskDdevExec()
                ->cmd($task);
        }
        $builder->taskDdevDescribe();
        return $builder;
    }

    function devDown()
    {
        $config = new Config();
        $builderConfig = $config->getExtraConfig('saitho-cli');

        /** @var $builder \RoboFile|CollectionBuilder */
        $builder = $this->getBuilder();
        $builder
            ->taskDdevStop()
                ->omitSnapshot()
                ->removeData();

        $devConfig = $builderConfig['dev'] ?? [];
        if (!empty($devConfig['mirror-dirs']) && is_array($devConfig['mirror-dirs'])) {
            foreach (array_values($devConfig['mirror-dirs']) as $targetDir) {
                $builder->taskDeleteDir($targetDir);
            }
        }

        return $builder;
    }

    function devStart()
    {
        /** @var $this \RoboFile */
        $this->taskDdevStart()
            ->run();
    }

    function devStop()
    {
        /** @var $this \RoboFile */
        $this->taskDdevStop()
            ->run();
    }
}
