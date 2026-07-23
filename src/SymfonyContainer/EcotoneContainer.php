<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Error;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Throwable;

/**
 * licence Apache-2.0
 */
final class EcotoneContainer implements ContainerInterface
{
    public function __construct(
        private SymfonyContainerInterface $container,
        private ContainerInterface $externalContainer,
        private ?string $loadedFromCachePath = null,
    ) {
    }

    public function get(string $id): mixed
    {
        $normalizedId = ServiceIdNormalizer::normalize($id);
        if ($this->container->has($normalizedId)) {
            try {
                return $this->container->get($normalizedId);
            } catch (Error $failure) {
                throw $this->attributeStaleCacheFailure($failure);
            }
        }

        return ExternalReferenceResolver::resolve($this->externalContainer, $id, ContainerImplementation::EXCEPTION_ON_INVALID_REFERENCE);
    }

    /**
     * A production cache is trusted and never rescanned, so after a deploy
     * removes or renames a class the cached container may still reference it.
     * The resulting load failure must say so — a bare "Class not found" gives
     * no hint that clearing the Ecotone cache is the fix.
     */
    private function attributeStaleCacheFailure(Error $failure): Throwable
    {
        if (
            $this->loadedFromCachePath === null
            || preg_match('/(?:Class|Interface|Trait|Enum) "([^"]+)" not found/', $failure->getMessage(), $match) !== 1
        ) {
            return $failure;
        }

        return ConfigurationException::createFromPreviousException(
            sprintf(
                'Ecotone\'s cached container references "%s", which can no longer be loaded. If it was removed or renamed since the cache was built, the production cache at "%s" is stale — clear the Ecotone cache (ecotone:cache:clear, or delete that directory) and boot again. If the error persists after clearing, the class is referenced by your own code. Original error: %s',
                $match[1],
                $this->loadedFromCachePath,
                $failure->getMessage(),
            ),
            $failure,
        );
    }

    public function has(string $id): bool
    {
        return $this->container->has(ServiceIdNormalizer::normalize($id)) || $this->externalContainer->has($id);
    }

    public function set(string $id, mixed $service): void
    {
        $this->container->set(ServiceIdNormalizer::normalize($id), $service);
    }

    public function getParameter(string $name): mixed
    {
        return $this->container->getParameter($name);
    }

    /**
     * @return string[]
     */
    public function getServiceIds(): array
    {
        return $this->container->getServiceIds();
    }

    /**
     * @return string[]
     */
    public function getDefinedServiceIds(): array
    {
        return array_values(array_filter(
            $this->getServiceIds(),
            fn (string $serviceId) => ! str_ends_with($serviceId, SymfonyContainerImplementation::EXTERNAL_DELEGATE_SUFFIX)
                && ! str_ends_with($serviceId, SymfonyContainerImplementation::NULLABLE_EXTERNAL_DELEGATE_SUFFIX)
                && $serviceId !== SymfonyContainerImplementation::EXTERNAL_CONTAINER_ID
                && $serviceId !== 'service_container'
                && $serviceId !== ContainerInterface::class,
        ));
    }

    /**
     * @return \Ecotone\Messaging\Config\ConsoleCommandConfiguration[]
     */
    public function getRegisteredConsoleCommands(): array
    {
        return unserialize($this->container->getParameter(SymfonyContainerImplementation::CONSOLE_COMMANDS_PARAMETER));
    }

    /**
     * @return string[]
     */
    public function getExternalReferenceIds(): array
    {
        return $this->container->getParameter(SymfonyContainerImplementation::EXTERNAL_REFERENCES_PARAMETER);
    }

    /**
     * @param callable(string $referenceName, string $interfaceName, callable(): object $factory): void $register
     */
    public function registerBridgesInto(callable $register): void
    {
        /** @var ConfiguredMessagingSystem $messagingSystem */
        $messagingSystem = $this->get(ConfiguredMessagingSystem::class);
        $register(ConfiguredMessagingSystem::class, ConfiguredMessagingSystem::class, fn () => $messagingSystem);
        foreach ($messagingSystem->getGatewayList() as $gatewayReference) {
            $referenceName = $gatewayReference->getReferenceName();
            $register($referenceName, $gatewayReference->getInterfaceName(), fn () => $this->get($referenceName));
        }
    }

    public function getConfigHash(): ?string
    {
        if (! $this->container->hasParameter(SymfonyContainerImplementation::CONFIG_HASH_PARAMETER)) {
            return null;
        }

        return $this->container->getParameter(SymfonyContainerImplementation::CONFIG_HASH_PARAMETER);
    }
}
