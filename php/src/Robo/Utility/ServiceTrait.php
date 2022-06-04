<?php
namespace Saitho\CLI\Robo\Utility;

use Robo\Contract\BuilderAwareInterface;

trait ServiceTrait {
    protected function getServiceInstance(string $className, array $args = [])
    {
        $reflection = new \ReflectionClass($className);
        $service = $reflection->newInstanceArgs($args);
        if ($service instanceof BuilderAwareInterface) {
            $service->setBuilder($this->getBuilder());
        }
        return $service;
    }
}
