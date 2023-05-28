<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\LazyConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Psr\Container\ContainerInterface;

class PsrContainerReferenceSearchService implements ReferenceSearchService
{
    private ContainerInterface $container;
    private array $defaults;

    private LazyConfiguredMessagingSystem $lazyConfiguredMessagingSystem;

    public function __construct(ContainerInterface $container, array $defaults = [])
    {
        $this->container = $container;
        $this->defaults = $defaults;
        $this->lazyConfiguredMessagingSystem = new LazyConfiguredMessagingSystem();
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference): object
    {
        if (! $this->container->has($reference)) {
            if (array_key_exists($reference, $this->defaults)) {
                return $this->defaults[$reference];
            }
            if ($reference === ConfiguredMessagingSystem::class) {
                return $this->lazyConfiguredMessagingSystem;
            }

            if ($this->container->has($reference . self::POSSIBLE_REFERENCE_SUFFIX)) {
                return $this->container->get($reference . self::POSSIBLE_REFERENCE_SUFFIX);
            }

            throw ReferenceNotFoundException::create("Reference {$reference} was not found");
        }

        return $this->container->get($reference);
    }

    public function has(string $referenceName): bool
    {
        if (! $this->container->has($referenceName)) {
            if (array_key_exists($referenceName, $this->defaults)) {
                return true;
            }

            return $this->container->has($referenceName . self::POSSIBLE_REFERENCE_SUFFIX);
        }

        return true;
    }

    public function setConfiguredMessagingSystem(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        $this->lazyConfiguredMessagingSystem->replaceWith($configuredMessagingSystem);
    }

    public static function getServiceNameWithSuffix(string $referenceName)
    {
        return $referenceName . self::POSSIBLE_REFERENCE_SUFFIX;
    }
}
