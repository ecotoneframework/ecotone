<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\LicensingException;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class ServiceActivatorModule extends MessageHandlerRegisterConfiguration
{
    /**
     * @var AnnotatedFinding[]
     */
    private array $changingHeadersFindings = [];

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $instance = parent::create($annotationRegistrationService, $interfaceToCallRegistry);

        foreach ($annotationRegistrationService->findAnnotatedMethods(static::getMessageHandlerAnnotation()) as $annotationRegistration) {
            /** @var InternalHandler $annotation */
            $annotation = $annotationRegistration->getAnnotationForMethod();
            if ($annotation->changingHeaders) {
                $instance->changingHeadersFindings[] = $annotationRegistration;
            }
        }

        return $instance;
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if ($this->changingHeadersFindings && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            $firstFinding = $this->changingHeadersFindings[0];
            throw LicensingException::create("{$firstFinding->getClassName()}::{$firstFinding->getMethodName()} is using changingHeaders, which is available only with Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.");
        }

        parent::prepare($messagingConfiguration, $extensionObjects, $moduleReferenceSearchService, $interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration, InterfaceToCallRegistry $interfaceToCallRegistry): MessageHandlerBuilderWithParameterConverters
    {
        if ($annotationRegistration->hasClassAnnotation(Saga::class) || $annotationRegistration->hasClassAnnotation(Aggregate::class)) {
            throw InvalidArgumentException::create("Message Handler or Service Activator works as stateless Handler and can't be used on Aggregate or Saga");
        }

        /** @var InternalHandler $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return MessageProcessorActivatorBuilder::createForMethodInvoker(
            MethodInvokerBuilder::create(
                AnnotatedDefinitionReference::getReferenceFor($annotationRegistration),
                new InterfaceToCallReference($annotationRegistration->getClassName(), $annotationRegistration->getMethodName()),
            )->withChangeHeaders($annotation->changingHeaders)
        )
            ->withEndpointId($annotation->getEndpointId())
            ->withRequiredReply($annotation->requiresReply)
            ->withOutputMessageChannel($annotation->getOutputChannelName())
            ->withInputChannelName($annotation->getInputChannelName())
            ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames());
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return InternalHandler::class;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
