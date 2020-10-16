<?php

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SymfonyReferenceTypeResolver
 * @package Ecotone\SymfonyBundle
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TypeResolver implements ReferenceTypeFromNameResolver
{
    private \Psr\Container\ContainerInterface $container;

    /**
     * SymfonyReferenceTypeResolver constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $referenceName
     * @return Type
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function resolve(string $referenceName): Type
    {
        if (!$this->container->has($referenceName)) {
            throw ReferenceNotFoundException::create("Reference {$referenceName} was not found");
        }


        return TypeDescriptor::create(get_class($this->container->get($referenceName)));
    }
}