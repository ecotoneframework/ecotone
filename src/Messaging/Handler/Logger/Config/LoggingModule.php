<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\LogAfter;
use Ecotone\Api\Attribute\LogBefore;
use Ecotone\Api\Attribute\LogError;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class LoggingModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messagingConfiguration->registerServiceDefinition(LoggingInterceptor::class, [
            Reference::to(LoggingGateway::class),
            Reference::to(ConversionService::class),
        ]);

        $messagingConfiguration->registerBeforeMethodInterceptor(
            MethodInterceptorBuilder::create(
                Reference::to(LoggingInterceptor::class),
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logBefore'),
                Precedence::EXCEPTION_LOGGING_PRECEDENCE,
                LogBefore::class
            )
        );
        $messagingConfiguration->registerAfterMethodInterceptor(
            MethodInterceptorBuilder::create(
                Reference::to(LoggingInterceptor::class),
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logAfter'),
                Precedence::EXCEPTION_LOGGING_PRECEDENCE,
                LogAfter::class
            )
        );
        $messagingConfiguration->registerAroundMethodInterceptor(
            AroundInterceptorBuilder::create(
                LoggingInterceptor::class,
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logException'),
                Precedence::EXCEPTION_LOGGING_PRECEDENCE,
                LogError::class . '||' . AsynchronousRunningEndpoint::class,
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
