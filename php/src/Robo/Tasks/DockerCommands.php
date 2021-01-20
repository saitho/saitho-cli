<?php
namespace Saitho\CLI\Robo\Tasks;

use Droath\RoboDockerCompose\Task\Base;
use Droath\RoboDockerCompose\Task\Down;
use Droath\RoboDockerCompose\Task\Restart;
use Droath\RoboDockerCompose\Task\Start;
use Droath\RoboDockerCompose\Task\Stop;
use Droath\RoboDockerCompose\Task\Up;
use Saitho\CLI\Robo\Utility\Config;
use Robo\TaskAccessor;

trait DockerCommands {
    use TaskAccessor;

    /**
     * @param  string  $configurationName
     * @throws \Exception
     */
    public function dockerUp(string $configurationName)
    {
        /** @var Up $task */
        $task = $this->getDockerComposeTask($configurationName, Up::class);
        $task->detachedMode()->run();
    }

    /**
     * @param  string  $configurationName
     * @throws \Exception
     */
    public function dockerDown(string $configurationName)
    {
        /** @var Down $task */
        $task = $this->getDockerComposeTask($configurationName, Down::class);
        $task->run();
    }

    /**
     * @param  string  $configurationName
     * @throws \Exception
     */
    public function dockerStart(string $configurationName)
    {
        /** @var Start $task */
        $task = $this->getDockerComposeTask($configurationName, Start::class);
        $task->run();
    }

    /**
     * @param  string  $configurationName
     * @throws \Exception
     */
    public function dockerStop(string $configurationName)
    {
        /** @var Stop $task */
        $task = $this->getDockerComposeTask($configurationName, Stop::class);
        $task->run();
    }

    /**
     * @param  string  $configurationName
     * @throws \Exception
     */
    public function dockerRestart(string $configurationName)
    {
        /** @var Restart $task */
        $task = $this->getDockerComposeTask($configurationName, Restart::class);
        $task->run();
    }

    /**
     * @param  string  $configurationName
     * @param  string  $className
     * @throws \Exception
     */
    protected function getDockerComposeTask(string $configurationName, string $className)
    {
        $dockerComposeConfig = $this->getExtraConfig()['docker']['compose'];
        $configurationNames = array_keys($dockerComposeConfig);
        if (!in_array($configurationName, $configurationNames)) {
            throw new \Exception(
                sprintf('Unknown Docker configuration "%s"', $configurationName)
            );
        }
        $task = $this->task($className);
        foreach ($dockerComposeConfig[$configurationName] as $file) {
            $task->file($file);
        }
        return $task;
    }
}
