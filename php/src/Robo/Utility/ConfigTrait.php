<?php
namespace Saitho\CLI\Robo\Utility;

trait ConfigTrait {

    protected function getExtraConfig($name = 'saitho-cli'): array
    {
        $config = new Config();
        return $config->getExtraConfig($name) ?? [];
    }
}
