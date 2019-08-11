<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\BeforeSend;
use Ecotone\Messaging\Annotation\Interceptor\EnricherInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\EnrichHeader;
use Ecotone\Messaging\Annotation\Interceptor\EnrichPayload;
use Ecotone\Messaging\Annotation\Interceptor\GatewayInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptorAnnotation;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptors;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\TransformerInterceptor;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayInterceptorBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class MethodInterceptorModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class MethodInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "methodInterceptorModule";
    /**
     * @var array|MethodInterceptor[]
     */
    private $postCallInterceptors;
    /**
     * @var array|MethodInterceptor[]
     */
    private $preCallInterceptors;
    /**
     * @var array|AroundInterceptorReference[]
     */
    private $aroundInterceptors;
    /**
     * @var array|MethodInterceptor[]
     */
    private $beforeSendInterceptors;

    /**
     * MethodInterceptorModule constructor.
     * @param MethodInterceptor[] $beforeSendInterceptors
     * @param MethodInterceptor[] $preCallInterceptors
     * @param AroundInterceptorReference[] $aroundInterceptors
     * @param MethodInterceptor[] $postCallInterceptors
     */
    private function __construct(array $beforeSendInterceptors, array $preCallInterceptors, array $aroundInterceptors, array $postCallInterceptors)
    {
        $this->preCallInterceptors = $preCallInterceptors;
        $this->postCallInterceptors = $postCallInterceptors;
        $this->aroundInterceptors = $aroundInterceptors;
        $this->beforeSendInterceptors = $beforeSendInterceptors;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): MethodInterceptorModule
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        /** @var AnnotationRegistration[] $methodsInterceptors */
        $methodsInterceptors = array_merge(
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, BeforeSend::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Before::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Around::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, After::class)
        );

        $beforeSendAnnotation = TypeDescriptor::create(BeforeSend::class);
        $beforeAnnotation = TypeDescriptor::create(Before::class);
        $aroundAnnotation = TypeDescriptor::create(Around::class);
        $afterAnnotation = TypeDescriptor::create(After::class);

        $beforeSendInterceptors = [];
        $preCallInterceptors = [];
        $aroundInterceptors = [];
        $postCallInterceptors = [];
        foreach ($methodsInterceptors as $methodInterceptor) {
            $interfaceToCall = InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName());

            if ($interfaceToCall->hasMethodAnnotation($aroundAnnotation)) {
                /** @var Around $aroundInterceptor */
                $aroundInterceptor = $interfaceToCall->getMethodAnnotation($aroundAnnotation);
                $aroundInterceptors[] = AroundInterceptorReference::create(
                    $methodInterceptor->getReferenceName(),
                    $methodInterceptor->getReferenceName(),
                    $methodInterceptor->getMethodName(),
                    $aroundInterceptor->precedence,
                    $aroundInterceptor->pointcut
                );
            }

            if ($interfaceToCall->hasMethodAnnotation($beforeSendAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeSendInterceptor = $interfaceToCall->getMethodAnnotation($beforeSendAnnotation);
                $beforeSendInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName()),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interfaceToCall),
                    $beforeSendInterceptor->precedence,
                    $beforeSendInterceptor->pointcut
                );
            }

            if ($interfaceToCall->hasMethodAnnotation($beforeAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeInterceptor = $interfaceToCall->getMethodAnnotation($beforeAnnotation);
                $preCallInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName()),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interfaceToCall),
                    $beforeInterceptor->precedence,
                    $beforeInterceptor->pointcut
                );
            }

            if ($interfaceToCall->hasMethodAnnotation($afterAnnotation)) {
                /** @var After $afterInterceptor */
                $afterInterceptor = $interfaceToCall->getMethodAnnotation($afterAnnotation);
                $postCallInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName()),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interfaceToCall),
                    $afterInterceptor->precedence,
                    $afterInterceptor->pointcut
                );
            }
        }

        return new self($beforeSendInterceptors, $preCallInterceptors, $aroundInterceptors, $postCallInterceptors);
    }

    /**
     * @param AnnotationRegistration $methodInterceptor
     * @param ParameterConverterAnnotationFactory $parameterConverterFactory
     * @param InterfaceToCall $interfaceToCall
     * @return MessageHandlerBuilderWithOutputChannel
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    private static function createMessageHandler(AnnotationRegistration $methodInterceptor, ParameterConverterAnnotationFactory $parameterConverterFactory, InterfaceToCall $interfaceToCall): MessageHandlerBuilderWithOutputChannel
    {
        /** @var After|Before $annotationForMethod */
        $annotationForMethod = $methodInterceptor->getAnnotationForMethod();
        $isTransformer = $annotationForMethod->changeHeaders;
        $parameterConverters = $annotationForMethod->parameterConverters;

        $methodParameterConverterBuilders = $parameterConverterFactory->createParameterConverters($interfaceToCall, $parameterConverters);
        if (!$methodParameterConverterBuilders) {
            $methodParameterConverterBuilders = MethodInvoker::createDefaultMethodParameters($interfaceToCall, $methodParameterConverterBuilders, false);
        }
        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if (self::hasParameterConverterFor($methodParameterConverterBuilders, $interfaceParameter)) {
                continue;
            }

            $methodParameterConverterBuilders[] = ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeHint());
        }

        if ($isTransformer) {
            return TransformerBuilder::create($methodInterceptor->getReferenceName(), $methodInterceptor->getMethodName())
                ->withMethodParameterConverters($methodParameterConverterBuilders);
        }

        $messageHandler = ServiceActivatorBuilder::create($methodInterceptor->getReferenceName(), $methodInterceptor->getMethodName())
            ->withPassThroughMessageOnVoidInterface(true)
            ->withMethodParameterConverters($methodParameterConverterBuilders);

        return $messageHandler;
    }

    /**
     * @param $methodParameterConverterBuilders
     * @param \Ecotone\Messaging\Handler\InterfaceParameter $interfaceParameter
     * @return bool
     */
    private static function hasParameterConverterFor($methodParameterConverterBuilders, \Ecotone\Messaging\Handler\InterfaceParameter $interfaceParameter): bool
    {
        foreach ($methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            if ($methodParameterConverterBuilder->isHandling($interfaceParameter)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->beforeSendInterceptors as $interceptor) {
            $configuration->registerBeforeSendInterceptor($interceptor);
        }
        foreach ($this->preCallInterceptors as $preCallInterceptor) {
            $configuration->registerBeforeMethodInterceptor($preCallInterceptor);
        }
        foreach ($this->aroundInterceptors as $aroundInterceptorReference) {
            $configuration->registerAroundMethodInterceptor($aroundInterceptorReference);
        }
        foreach ($this->postCallInterceptors as $postCallInterceptor) {
            $configuration->registerAfterMethodInterceptor($postCallInterceptor);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }
}