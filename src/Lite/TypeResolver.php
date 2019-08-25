<?php

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
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
    /**
     * @var ContainerInterface
     */
    private $container;

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
     * @return TypeDescriptor
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function resolve(string $referenceName): TypeDescriptor
    {
        if (!$this->container->has($referenceName)) {
            throw ReferenceNotFoundException::create("Reference {$referenceName} was not found");
        }


        return TypeDescriptor::create(get_class($this->container->get($referenceName)));
    }
}