<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Utility\Config;
use Robo\Collection\CollectionBuilder;
use Saitho\CLI\Robo\Utility\ConfigTrait;

trait DevCommands {
    use ConfigTrait;

    function devUp()
    {
        $devConfig = $this->getExtraConfig()['dev'] ?? [];

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
        /** @var $builder \RoboFile|CollectionBuilder */
        $builder = $this->getBuilder();
        $builder
            ->taskDdevStop()
                ->omitSnapshot()
                ->removeData();

        $devConfig = $this->getExtraConfig()['dev'] ?? [];
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
