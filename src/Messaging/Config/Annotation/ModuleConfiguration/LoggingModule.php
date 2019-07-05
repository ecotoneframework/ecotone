<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogAfter;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogBefore;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogError;
use SimplyCodedSoftware\Messaging\Handler\Logger\ExceptionLoggingInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Handler\Logger\LoggingHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Logger\LoggingInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;

/**
 * Class LoggingModule
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class LoggingModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "loggingModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $configuration->registerBeforeMethodInterceptor(
            MethodInterceptor::create(
                "beforeLog",
                InterfaceToCall::create(LoggingInterceptor::class, "logBefore"),
                LoggingHandlerBuilder::createForBefore(),
                -1,
                LogBefore::class
            )
        );
        $configuration->registerAfterMethodInterceptor(
            MethodInterceptor::create(
                "afterLog",
                InterfaceToCall::create(LoggingInterceptor::class, "logAfter"),
                LoggingHandlerBuilder::createForAfter(),
                -1,
                LogAfter::class
            )
        );
        $configuration->registerAroundMethodInterceptor(
            AroundInterceptorReference::createWithObjectBuilder(
                "errorLog",
                new ExceptionLoggingInterceptorBuilder(),
                "logException",
                -1,
                LogError::class
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
}