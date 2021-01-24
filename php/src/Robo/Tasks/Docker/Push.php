<?php

namespace Saitho\CLI\Robo\Tasks\Docker;

use Robo\Task\Docker\Base;

/**
 * Pushes a Docker image
 *
 * ```php
 * <?php *
 * $this->taskDockerPush('myimage:latest')
 *      ->run();
 *
 * ?>
 *
 * ```
 *
 * Class Push
 * @package Robo\Task\Docker
 */
class Push extends Base
{
    /**
     * @var string
     */
    protected $imageName;

    /**
     * @param string $path
     */
    public function __construct(string $imageName)
    {
        $this->command = "docker push";
        $this->imageName = $imageName;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        $command = $this->command;
        return $command . ' ' . $this->arguments . ' ' . $this->imageName;
    }
}
