<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection\Compiler;

use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SymfonyReferenceTypeResolver
 * @package Ecotone\SymfonyBundle
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SymfonyReferenceTypeResolver implements ReferenceTypeFromNameResolver
{
    /**
     * @var ContainerBuilder|ContainerInterface
     */
    private $container;

    /**
     * SymfonyReferenceTypeResolver constructor.
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param string $referenceName
     * @return Type
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function resolve(string $referenceName): Type
    {
        if ($this->container instanceof ContainerBuilder) {
            return TypeDescriptor::create($this->container->getDefinition($referenceName)->getClass());
        } else {
            return TypeDescriptor::create(get_class($this->container->get($referenceName . SymfonyReferenceSearchService::REFERENCE_SUFFIX)));
        }
    }
}
