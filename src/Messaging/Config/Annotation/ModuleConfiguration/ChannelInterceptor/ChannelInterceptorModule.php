<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ChannelInterceptor;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ChannelInterceptor;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor\BeforeSendChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
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
 */
final class ChannelInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @param AnnotatedFinding[] $channelInterceptors
     */
    private function __construct(private array $channelInterceptors)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self($annotationRegistrationService->findAnnotatedMethods(ChannelInterceptor::class));
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (empty($this->channelInterceptors)) {
            return;
        }

        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            $firstFinding = $this->channelInterceptors[0];
            throw LicensingException::create("ChannelInterceptor attribute on {$firstFinding->getClassName()}::{$firstFinding->getMethodName()} is available only with Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.");
        }

        foreach ($this->channelInterceptors as $channelInterceptorFinding) {
            /** @var ChannelInterceptor $channelInterceptor */
            $channelInterceptor = $channelInterceptorFinding->getAnnotationForMethod();
            $interceptorInterface = $interfaceToCallRegistry->getFor($channelInterceptorFinding->getClassName(), $channelInterceptorFinding->getMethodName());

            $methodInterceptorBuilder = MethodInterceptorBuilder::create(
                new Reference(AnnotatedDefinitionReference::getReferenceFor($channelInterceptorFinding)),
                $interceptorInterface,
                $channelInterceptor->getPrecedence(),
                '',
                $channelInterceptor->isChangeHeaders(),
                ParameterConverterAnnotationFactory::createParameterConverters($interceptorInterface),
            );

            $messagingConfiguration->registerChannelInterceptor(new BeforeSendChannelInterceptorBuilder(
                $methodInterceptorBuilder,
                $channelInterceptor->getChannelName(),
                InterfaceToCallReference::fromInstance($interceptorInterface),
                [],
            ));
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
