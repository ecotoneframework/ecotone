<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection\Compiler;

use Ecotone\Messaging\Handler\ReferenceSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SymfonyReferenceSearchService implements ReferenceSearchService
{
    public const REFERENCE_SUFFIX = '-proxy';

    public function __construct(private ContainerInterface $container)
    {
    }

    public function get(string $reference): object
    {
        return $this->container->get($reference . self::REFERENCE_SUFFIX);
    }

    public function has(string $referenceName): bool
    {
        return $this->container->has($referenceName . self::REFERENCE_SUFFIX);
    }
}
