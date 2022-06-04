<?php
namespace Saitho\CLI\Robo\Utility;

use Robo\Contract\BuilderAwareInterface;

trait ServiceTrait {
    protected function getServiceInstance(string $className, array $args = [])
    {
        $reflection = new \ReflectionClass($className);
        $service = $reflection->newInstanceArgs($args);

        // Can't check for BuilderAwareInterface on service...
        if ($this instanceof BuilderAwareInterface && method_exists($service, 'setBuilder')) {
            $service->setBuilder($this->getBuilder());
        }
        return $service;
    }
}
