<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Collected;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Gathered;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Messaging\CollectedMessages;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gathering\CollectedHandlerContext;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\LicensingException;

#[ModuleAnnotation]
/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 */
final class CollectingModule extends NoExternalConfigurationModule
{
    public function __construct(private AnnotationFinder $annotationFinder)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        self::validateExplicitEndpointIds($annotationRegistrationService, $interfaceToCallRegistry);
        return new self($annotationRegistrationService);
    }

    private static function validateExplicitEndpointIds(AnnotationFinder $finder, InterfaceToCallRegistry $registry): void
    {
        foreach ($finder->findAnnotatedMethods(Collected::class) as $registration) {
            $interface = $registry->getFor($registration->getClassName(), $registration->getMethodName());
            foreach ($interface->getMethodAnnotations() as $handler) {
                if (! $handler instanceof InternalHandler && ! $handler instanceof CommandHandler && ! $handler instanceof EventHandler) {
                    continue;
                }
                if (! $handler->isEndpointIdGenerated()) {
                    continue;
                }
                $context = new CollectedHandlerContext(className: $registration->getClassName(), methodName: $registration->getMethodName(), endpointId: '', groupByDescription: '');
                $attribute = match (true) {
                    $handler instanceof CommandHandler => '#[CommandHandler(routingKey: ' . var_export($handler->getInputChannelName(), true) . ", endpointId: 'your.stable.id')]",
                    $handler instanceof EventHandler => '#[EventHandler(listenTo: ' . var_export($handler->listenTo, true) . ", endpointId: 'your.stable.id')]",
                    default => '#[InternalHandler(inputChannelName: ' . var_export($handler->getInputChannelName(), true) . ", endpointId: 'your.stable.id')]",
                };
                throw ConfigurationException::create("Collected handler {$context->description} has no explicit endpointId. Open batches are stored under endpointId, so it must stay stable across deploys. Set {$attribute}, or remove #[Collected].");
            }
        }
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->annotationFinder->findAnnotatedMethods(Collected::class) as $registration) {
            $interface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            $handlers = array_values(array_filter($interface->getMethodAnnotations(), static fn (object $attribute): bool => $attribute instanceof InternalHandler || $attribute instanceof CommandHandler || $attribute instanceof EventHandler));
            $context = new CollectedHandlerContext(className: $registration->getClassName(), methodName: $registration->getMethodName(), endpointId: $handlers === [] ? '' : $handlers[0]->getEndpointId(), groupByDescription: '');
            $handlerDescription = $context->description;
            $this->validateHandlerKind(interface: $interface, handlers: $handlers, handlerDescription: $handlerDescription);
            $this->validateSubscriptionAndAsynchronous(interface: $interface, handlers: $handlers, handlerDescription: $handlerDescription);
            $this->validateReleaseLimits($registration->getAnnotationForMethod(), $handlerDescription);
            $this->validateLicence($messagingConfiguration->isRunningForEnterpriseLicence(), $handlerDescription);
            $this->validateBatchParameter($interface, $handlerDescription);
        }
    }

    private function validateHandlerKind(InterfaceToCall $interface, array $handlers, string $handlerDescription): void
    {
        $handlerCount = count($handlers) + ($interface->hasMethodAnnotation(QueryHandler::class) ? 1 : 0);
        if ($handlerCount > 1) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} has {$handlerCount} handler attributes. A collected method must have exactly one #[CommandHandler], #[EventHandler] or #[InternalHandler]. Keep one handler attribute, move the other handler to a separate method, or remove #[Collected].");
        }
        if ($interface->hasMethodAnnotation(Gathered::class)) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} also has #[Gathered]: collecting ordinary messages and gathering split parts cannot share a handler. Remove #[Gathered] to collect ordinary messages, or remove #[Collected] to gather split parts.");
        }
        if ($interface->hasClassAnnotation(Aggregate::class)) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} belongs to an aggregate or saga, but a batch has no single instance to load. Move #[Collected] to a service #[CommandHandler], #[EventHandler] or #[InternalHandler], or remove #[Collected].");
        }
        if ($interface->hasAnnotation(QueryHandler::class)) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} is not an allowed message handler: a query caller waits for an answer. Stack #[Collected] on a service #[CommandHandler], #[EventHandler] or #[InternalHandler] with #[Asynchronous], or remove #[Collected].");
        }
        if ($handlers === []) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} has no ordinary message handler attribute. Stack it on a service #[CommandHandler], #[EventHandler] or #[InternalHandler], or remove #[Collected].");
        }
    }

    private function validateSubscriptionAndAsynchronous(InterfaceToCall $interface, array $handlers, string $handlerDescription): void
    {
        foreach ($handlers as $handler) {
            if ($handler instanceof InternalHandler && $handler->getInputChannelName() === '') {
                throw ConfigurationException::create("Collected handler {$handlerDescription} cannot infer its subscription because the first parameter is CollectedMessages. Set #[InternalHandler(inputChannelName: 'your.input.channel', endpointId: '{$handler->getEndpointId()}')], or remove #[Collected].");
            }
            if ($handler instanceof CommandHandler && $handler->getInputChannelName() === '') {
                throw ConfigurationException::create("Collected handler {$handlerDescription} cannot infer its subscription because the first parameter is CollectedMessages. Set #[CommandHandler(routingKey: 'your.routing.key', endpointId: '{$handler->getEndpointId()}')], or remove #[Collected].");
            }
            if ($handler instanceof EventHandler && $handler->listenTo === '') {
                throw ConfigurationException::create("Collected handler {$handlerDescription} cannot infer its subscription because the first parameter is CollectedMessages. Set #[EventHandler(listenTo: YourEvent::class, endpointId: '{$handler->getEndpointId()}')], or remove #[Collected].");
            }
        }
        if (! $interface->hasAnnotation(Asynchronous::class)) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} requires an asynchronous channel. Add #[Asynchronous('channelName')] to the handler, or remove #[Collected].");
        }
    }

    private function validateReleaseLimits(Collected $collected, string $handlerDescription): void
    {
        if ($collected->maxSize === null && $collected->maxWait === null) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} needs a release limit. Set maxSize or maxWait on #[Collected], or remove #[Collected].");
        }
        if ($collected->maxSize !== null && $collected->maxSize < 1) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} maxSize is {$collected->maxSize}; maxSize must be at least 1. Set maxSize to a positive integer, omit maxSize and set maxWait, or remove #[Collected].");
        }
        if ($collected->maxWait !== null && $collected->maxWait->toMilliseconds() <= 0) {
            $milliseconds = $collected->maxWait->toMilliseconds();
            throw ConfigurationException::create("Collected handler {$handlerDescription} maxWait is {$milliseconds} milliseconds; maxWait must be greater than 0. Set a positive TimeSpan, omit maxWait and set maxSize, or remove #[Collected].");
        }
    }

    private function validateLicence(bool $enterprise, string $handlerDescription): void
    {
        if (! $enterprise) {
            throw LicensingException::create("Collected handler {$handlerDescription} requires an Ecotone Enterprise licence. Provide your licence key as `licenceKey` in the Symfony, Laravel or Tempest Ecotone configuration, or as the `licenceKey:` argument of EcotoneLite, or start a trial at https://ecotone.tech/pricing. Alternatively, remove #[Collected].");
        }
    }

    private function validateBatchParameter(InterfaceToCall $interface, string $handlerDescription): void
    {
        if (! $interface->hasFirstParameter() || ! $interface->getFirstParameter()->getTypeDescriptor()->equals(Type::create(CollectedMessages::class))) {
            throw ConfigurationException::create("Collected handler {$handlerDescription} must receive Ecotone\\Api\\Messaging\\CollectedMessages as its first payload parameter. Change that parameter type to CollectedMessages, or remove #[Collected].");
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
