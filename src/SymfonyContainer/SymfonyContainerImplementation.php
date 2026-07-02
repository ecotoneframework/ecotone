<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\DefinitionHelper;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\DefinedObjectWrapper;

use function get_class;
use function in_array;
use function is_array;
use function is_string;
use function method_exists;

use Psr\Container\ContainerInterface;
use ReflectionMethod;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Definition as SymfonyDefinition;
use Symfony\Component\DependencyInjection\Reference as SymfonyReference;

/**
 * licence Apache-2.0
 */
class SymfonyContainerImplementation implements ContainerImplementation
{
    public const EXTERNAL_CONTAINER_ID = 'ecotone.external_container';
    public const EXTERNAL_REFERENCES_PARAMETER = 'ecotone.external_references';
    public const CONSOLE_COMMANDS_PARAMETER = 'ecotone.console_commands';
    public const CONFIG_HASH_PARAMETER = 'ecotone.config_hash';
    public const EXTERNAL_DELEGATE_SUFFIX = '.ecotone.external';
    public const NULLABLE_EXTERNAL_DELEGATE_SUFFIX = '.ecotone.external.nullable';

    /**
     * @var Definition[]|Reference[] $definitions
     */
    private array $definitions = [];

    private array $externalReferences = [];

    /**
     * @param string[] $syntheticServiceIds
     */
    public function __construct(
        private SymfonyContainerBuilder $symfonyBuilder,
        private array $syntheticServiceIds = [],
        private bool $preserveRuntimeInstances = false,
    ) {
    }

    public function process(ContainerBuilder $builder): void
    {
        $this->registerSyntheticService(self::EXTERNAL_CONTAINER_ID, ContainerInterface::class);
        $this->registerSyntheticService(ContainerInterface::class, ContainerInterface::class);
        foreach ($this->syntheticServiceIds as $syntheticServiceId) {
            $this->registerSyntheticService(ServiceIdNormalizer::normalize($syntheticServiceId), stdClass::class);
        }

        $this->definitions = $builder->getDefinitions();
        foreach ($this->definitions as $id => $definition) {
            if (in_array($id, $this->syntheticServiceIds, true)) {
                continue;
            }
            $symfonyDefinition = $this->resolveArgument($definition);
            if ($symfonyDefinition instanceof SymfonyReference) {
                $this->symfonyBuilder->setAlias(ServiceIdNormalizer::normalize($id), (string) $symfonyDefinition)->setPublic(true);
            } else {
                $this->symfonyBuilder->setDefinition(ServiceIdNormalizer::normalize($id), $symfonyDefinition);
            }
        }
        $this->symfonyBuilder->setParameter(self::EXTERNAL_REFERENCES_PARAMETER, array_values($this->externalReferences));
    }

    private function registerSyntheticService(string $id, string $className): void
    {
        $this->symfonyBuilder->setDefinition(
            $id,
            (new SymfonyDefinition($className))->setSynthetic(true)->setPublic(true)
        );
    }

    private function resolveArgument($argument): mixed
    {
        if ($this->preserveRuntimeInstances) {
            if ($argument instanceof DefinedObjectWrapper) {
                return $this->convertRuntimeInstanceDefinition($argument);
            }
            if ($argument instanceof DefinedObject) {
                return $this->runtimeInstanceDefinition($argument);
            }
        }
        if ($argument instanceof DefinedObject) {
            $argument = $argument->getDefinition();
        }
        if ($argument instanceof AttributeDefinition) {
            $argument = DefinitionHelper::resolvePotentialComplexAttribute($argument);
        }
        if ($argument instanceof Definition) {
            return $this->convertDefinition($argument);
        } elseif (is_array($argument)) {
            $resolvedArguments = [];
            foreach ($argument as $index => $value) {
                $resolvedArguments[$index] = $this->resolveArgument($value);
            }
            return $resolvedArguments;
        } elseif ($argument instanceof Reference) {
            return $this->resolveReference($argument);
        } else {
            return $argument;
        }
    }

    private function resolveReference(Reference $reference): SymfonyReference
    {
        $id = $reference->getId();
        if (isset($this->definitions[$id]) || $id === ContainerInterface::class || in_array($id, $this->syntheticServiceIds, true)) {
            return new SymfonyReference(ServiceIdNormalizer::normalize($id));
        }

        return new SymfonyReference($this->registerExternalReferenceDelegate($id, $reference->getInvalidBehavior()));
    }

    private function registerExternalReferenceDelegate(string $id, int $invalidBehavior): string
    {
        $delegateId = ServiceIdNormalizer::normalize($id) . ($invalidBehavior === self::NULL_ON_INVALID_REFERENCE
            ? self::NULLABLE_EXTERNAL_DELEGATE_SUFFIX
            : self::EXTERNAL_DELEGATE_SUFFIX);

        if (! $this->symfonyBuilder->hasDefinition($delegateId)) {
            $this->symfonyBuilder->setDefinition(
                $delegateId,
                (new SymfonyDefinition(stdClass::class))
                    ->setFactory([ExternalReferenceResolver::class, 'resolve'])
                    ->setArguments([new SymfonyReference(self::EXTERNAL_CONTAINER_ID), $id, $invalidBehavior])
                    ->setPublic(true)
            );
        }
        $this->externalReferences[$id] = $id;

        return $delegateId;
    }

    private function runtimeInstanceDefinition(object $instance): SymfonyDefinition
    {
        return (new SymfonyDefinition(get_class($instance)))
            ->setFactory([RuntimeInstanceProvider::class, 'provide'])
            ->setArguments([$instance])
            ->setPublic(true);
    }

    private function convertRuntimeInstanceDefinition(DefinedObjectWrapper $definedObjectWrapper): SymfonyDefinition
    {
        $instance = $definedObjectWrapper->instance();
        $sfDefinition = $this->runtimeInstanceDefinition($instance);
        foreach ($definedObjectWrapper->getMethodCalls() as $methodCall) {
            $sfDefinition->addMethodCall(
                $methodCall->getMethodName(),
                $this->normalizeNamedArgument($this->resolveArgument($methodCall->getArguments()))
            );
        }
        return $sfDefinition->setPublic(true);
    }

    private function convertDefinition(Definition $ecotoneDefinition): SymfonyDefinition
    {
        $sfDefinition = new SymfonyDefinition(
            $ecotoneDefinition->getClassName(),
            $this->normalizeNamedArgument($this->resolveArgument($ecotoneDefinition->getArguments()))
        );
        if ($ecotoneDefinition->hasFactory()) {
            $sfDefinition->setFactory($this->resolveFactoryArgument($ecotoneDefinition->getFactory()));
        }
        foreach ($ecotoneDefinition->getMethodCalls() as $methodCall) {
            $sfDefinition->addMethodCall(
                $methodCall->getMethodName(),
                $this->normalizeNamedArgument($this->resolveArgument($methodCall->getArguments()))
            );
        }
        return $sfDefinition->setPublic(true);
    }

    private function normalizeNamedArgument(array $arguments): array
    {
        foreach ($arguments as $index => $argument) {
            if (is_string($index)) {
                $arguments['$' . $index] = $argument;
                unset($arguments[$index]);
            }
        }
        return $arguments;
    }

    private function resolveFactoryArgument(array $factory): array
    {
        if (method_exists($factory[0], $factory[1]) && (new ReflectionMethod($factory[0], $factory[1]))->isStatic()) {
            return $factory;
        }

        return [$this->resolveReference(new Reference($factory[0])), $factory[1]];
    }
}
