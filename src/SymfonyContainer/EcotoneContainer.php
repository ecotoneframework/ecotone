<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;

/**
 * licence Apache-2.0
 */
final class EcotoneContainer implements ContainerInterface
{
    public function __construct(
        private SymfonyContainerInterface $container,
        private ContainerInterface $externalContainer,
    ) {
    }

    public function get(string $id): mixed
    {
        $normalizedId = ServiceIdNormalizer::normalize($id);
        if ($this->container->has($normalizedId)) {
            return $this->container->get($normalizedId);
        }

        return ExternalReferenceResolver::resolve($this->externalContainer, $id, ContainerImplementation::EXCEPTION_ON_INVALID_REFERENCE);
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
