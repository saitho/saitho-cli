<?php

namespace Saitho\CLI\Robo\Tasks\Docker;

trait loadTasks
{
    /**
     * @param string $image
     *
     * @return \Robo\Task\Docker\Run|\Robo\Collection\CollectionBuilder
     */
    protected function taskDockerPush(string $image)
    {
        return $this->task(Push::class, $image);
    }
}
