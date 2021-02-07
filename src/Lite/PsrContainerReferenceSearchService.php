<?php
declare(strict_types=1);


namespace Ecotone\Lite;

use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Psr\Container\ContainerInterface;

class PsrContainerReferenceSearchService implements ReferenceSearchService
{
    private ContainerInterface $container;
    private array $defaults;

    public function __construct(ContainerInterface $container, array $defaults = [])
    {
        $this->container = $container;
        $this->defaults = $defaults;
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference): object
    {
        if (!$this->container->has($reference)) {
            if (array_key_exists($reference, $this->defaults)) {
                return $this->defaults[$reference];
            }

            throw ReferenceNotFoundException::create("Reference {$reference} was not found");
        }

        return $this->container->get($reference);
    }

    public function has(string $referenceName): bool
    {
        if (!$this->container->has($referenceName)) {
            if (array_key_exists($referenceName, $this->defaults)) {
                return true;
            }

            return false;
        }

        return true;
    }
}