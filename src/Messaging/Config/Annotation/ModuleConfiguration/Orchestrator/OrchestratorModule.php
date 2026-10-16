<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\Orchestrator;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\Orchestrator;
use Ecotone\Api\Attribute\OrchestratorGateway;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\OrchestratorResultMessageConverter;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;

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
 */
final class OrchestratorModule implements AnnotationModule
{
    private const ORCHESTRATOR_GATEWAY_ENTRYPOINT_CHANNEL = 'ecotone.orchestrator.entrypoint';
    private const ORCHESTRATOR_ROUTING_SLIP_HEADER = 'ecotone.orchestrator.routing_slip';

    /**
     * @param MessageHandlerBuilderWithParameterConverters[] $orchestratorsServiceActivators
     * @param GatewayProxyBuilder[] $orchestratorGateways
     */
    private function __construct(
        private array $orchestratorsServiceActivators,
        private array $orchestratorGateways,
    ) {

    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $messageHandlerBuilders = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(Orchestrator::class) as $annotationRegistration) {
            $messageHandlerBuilders[] = self::createMessageHandlerFrom($annotationRegistration, $interfaceToCallRegistry);
        }
        $orchestratorGateways = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(OrchestratorGateway::class) as $annotationRegistration) {
            $interfaceToCall = $interfaceToCallRegistry->getFor($annotationRegistration->getClassName(), $annotationRegistration->getMethodName());
            Assert::isTrue(count($interfaceToCall->getInterfaceParameters()) >= 2, "Orchestrator Gateway {$interfaceToCall} must have at least two parameters. First for list of channels, second for payload, and optional third for headers.");
            Assert::isTrue(count($interfaceToCall->getInterfaceParameters()) <= 3, "Orchestrator Gateway {$interfaceToCall} can have maximum three parameters. First for list of channels, second for payload, and optional third for headers.");
            Assert::isTrue($interfaceToCall->getFirstParameter()->getTypeDescriptor()->isArrayButNotClassBasedCollection(), "Orchestrator Gateway {$interfaceToCall} first parameter must be array of strings for routed channels", true);

            $parameters = [
                /** Replaces routing slip completely, as gateway should be treated as totally new flow */
                GatewayHeaderBuilder::create($interfaceToCall->getFirstParameterName(), self::ORCHESTRATOR_ROUTING_SLIP_HEADER),
                GatewayPayloadBuilder::create($interfaceToCall->getSecondParameter()->getName()),
            ];

            if ($interfaceToCall->hasThirdParameter()) {
                Assert::isTrue($interfaceToCall->getThirdParameter()->getTypeDescriptor()->isArrayButNotClassBasedCollection(), "Orchestrator Gateway {$interfaceToCall} third parameter must be array of headers", true);

                $parameters[] = GatewayHeadersBuilder::create($interfaceToCall->getThirdParameter()->getName());
            }

            $orchestratorGateways[] = GatewayProxyBuilder::create(
                $annotationRegistration->getClassName(),
                $annotationRegistration->getClassName(),
                $annotationRegistration->getMethodName(),
                self::ORCHESTRATOR_GATEWAY_ENTRYPOINT_CHANNEL,
            )->withParameterConverters($parameters);
        }


        if (count($orchestratorGateways) > 0) {
            $messageHandlerBuilders[] = ServiceActivatorBuilder::createWithDefinition(
                new Definition(OrchestratorGatewayEntrypoint::class, []),
                'handle'
            )
                ->withInputChannelName(self::ORCHESTRATOR_GATEWAY_ENTRYPOINT_CHANNEL)
                ->withMethodParameterConverters([
                    HeaderBuilder::create('routingSlip', self::ORCHESTRATOR_ROUTING_SLIP_HEADER),
                ])
                ->withCustomResultToMessageConverter(
                    new Definition(OrchestratorResultMessageConverter::class, [MessageHeaders::ROUTING_SLIP, Reference::to(MessageBuilder::class)])
                );
        }

        return new self($messageHandlerBuilders, $orchestratorGateways);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->verifyOrchestratorLicense($messagingConfiguration);

        foreach ($this->orchestratorsServiceActivators as $messageHandlerBuilder) {
            $messagingConfiguration->registerMessageHandler($messageHandlerBuilder);
        }
        foreach ($this->orchestratorGateways as $gatewayProxyBuilder) {
            $messagingConfiguration->registerGatewayBuilder($gatewayProxyBuilder);
        }
    }

    /**
     * @inheritDoc
     */
    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration, InterfaceToCallRegistry $interfaceToCallRegistry): MessageHandlerBuilderWithParameterConverters
    {
        if ($annotationRegistration->hasClassAnnotation(Saga::class) || $annotationRegistration->hasClassAnnotation(Aggregate::class)) {
            throw InvalidArgumentException::create("Orchestrator works as stateless Handler and can't be used on Aggregate or Saga");
        }

        $interfaceToCall = $interfaceToCallRegistry->getFor($annotationRegistration->getClassName(), $annotationRegistration->getMethodName());

        // Validate return type - only array is allowed
        $returnType = $interfaceToCall->getReturnType();
        if ($returnType === null) {
            throw InvalidArgumentException::create(
                sprintf(
                    'Orchestrator method %s::%s must have explicit array return type declaration',
                    $annotationRegistration->getClassName(),
                    $annotationRegistration->getMethodName()
                )
            );
        }

        if ($returnType->isVoid()) {
            throw InvalidArgumentException::create(
                sprintf(
                    'Orchestrator method %s::%s must return array of strings, but returns void',
                    $annotationRegistration->getClassName(),
                    $annotationRegistration->getMethodName()
                )
            );
        }

        if ($returnType->isUnionType()) {
            throw InvalidArgumentException::create(
                sprintf(
                    'Orchestrator method %s::%s must return array of strings, but returns union type %s',
                    $annotationRegistration->getClassName(),
                    $annotationRegistration->getMethodName(),
                    $returnType->toString()
                )
            );
        }

        if ($interfaceToCall->canItReturnNull()) {
            throw InvalidArgumentException::create(
                sprintf(
                    'Orchestrator method %s::%s must return array of strings, but returns nullable type %s',
                    $annotationRegistration->getClassName(),
                    $annotationRegistration->getMethodName(),
                    $returnType->toString()
                )
            );
        }

        if (! $returnType->isArrayButNotClassBasedCollection()) {
            throw InvalidArgumentException::create(
                sprintf(
                    'Orchestrator method %s::%s must return array of strings, but returns %s',
                    $annotationRegistration->getClassName(),
                    $annotationRegistration->getMethodName(),
                    $returnType->toString()
                )
            );
        }

        /** @var Orchestrator $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($annotationRegistration), $interfaceToCall)
            ->withEndpointId($annotation->getEndpointId())
            ->withInputChannelName($annotation->getInputChannelName())
            ->withCustomResultToMessageConverter(
                new Definition(OrchestratorResultMessageConverter::class, [MessageHeaders::ROUTING_SLIP, Reference::to(MessageBuilder::class)])
            );
    }

    private function verifyOrchestratorLicense(Configuration $messagingConfiguration): void
    {
        if ($messagingConfiguration->isRunningForEnterpriseLicence()) {
            return;
        }

        if ($this->orchestratorsServiceActivators !== []) {
            throw LicensingException::create('Orchestrator attribute is available only with Ecotone Enterprise licence. This functionality requires enterprise mode to ensure proper workflow orchestration capabilities. See https://docs.ecotone.tech/enterprise for details.');
        }
    }
}
