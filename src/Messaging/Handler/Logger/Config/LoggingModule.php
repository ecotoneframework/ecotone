<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Precedence;
use Psr\Log\LoggerInterface;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class LoggingModule extends NoExternalConfigurationModule implements AnnotationModule
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
            new Definition(LoggingService::class, [Reference::to(ConversionService::REFERENCE_NAME), Reference::to(LoggerInterface::class)]),
        ]);

        $messagingConfiguration->registerBeforeMethodInterceptor(
            MethodInterceptor::create(
                'beforeLog',
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logBefore'),
                LoggingHandlerBuilder::createForBefore(),
                Precedence::EXCEPTION_LOGGING_PRECEDENCE,
                LogBefore::class
            )
        );
        $messagingConfiguration->registerAfterMethodInterceptor(
            MethodInterceptor::create(
                'afterLog',
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logAfter'),
                LoggingHandlerBuilder::createForAfter(),
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
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
