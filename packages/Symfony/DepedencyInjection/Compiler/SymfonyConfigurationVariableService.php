<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection\Compiler;

use Ecotone\Messaging\ConfigurationVariableService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SymfonyConfigurationVariableService implements ConfigurationVariableService
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function getByName(string $name)
    {
        return $this->container->get($name);
    }

    public function hasName(string $name): bool
    {
        return $this->container->hasParameter($name);
    }
}
