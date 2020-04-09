<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\Presend;
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
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

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
     * @var array
     */
    private $beforeSendRelatedInterfaces = [];

    /**
     * MethodInterceptorModule constructor.
     * @param MethodInterceptor[] $beforeSendInterceptors
     * @param MethodInterceptor[] $preCallInterceptors
     * @param AroundInterceptorReference[] $aroundInterceptors
     * @param MethodInterceptor[] $postCallInterceptors
     * @param array $relatedInterfaces
     */
    private function __construct(array $beforeSendInterceptors, array $preCallInterceptors, array $aroundInterceptors, array $postCallInterceptors, array $relatedInterfaces)
    {
        $this->preCallInterceptors = $preCallInterceptors;
        $this->postCallInterceptors = $postCallInterceptors;
        $this->aroundInterceptors = $aroundInterceptors;
        $this->beforeSendInterceptors = $beforeSendInterceptors;
        $this->beforeSendRelatedInterfaces = $relatedInterfaces;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): MethodInterceptorModule
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        /** @var AnnotationRegistration[] $methodsInterceptors */
        $methodsInterceptors = array_merge(
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Presend::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Before::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Around::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, After::class)
        );

        $beforeSendAnnotation = TypeDescriptor::create(Presend::class);
        $beforeAnnotation = TypeDescriptor::create(Before::class);
        $aroundAnnotation = TypeDescriptor::create(Around::class);
        $afterAnnotation = TypeDescriptor::create(After::class);

        $beforeSendInterceptors = [];
        $relatedInterfaces = [];
        $preCallInterceptors = [];
        $aroundInterceptors = [];
        $postCallInterceptors = [];
        foreach ($methodsInterceptors as $methodInterceptor) {
            $interceptorInterface = InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName());

            if ($interceptorInterface->hasMethodAnnotation($aroundAnnotation)) {
                /** @var Around $aroundInterceptor */
                $aroundInterceptor = $interceptorInterface->getMethodAnnotation($aroundAnnotation);
                $aroundInterceptors[] = AroundInterceptorReference::create(
                    $methodInterceptor->getReferenceName(),
                    $methodInterceptor->getReferenceName(),
                    $methodInterceptor->getMethodName(),
                    $aroundInterceptor->precedence,
                    $aroundInterceptor->pointcut
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($beforeSendAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeSendInterceptor = $interceptorInterface->getMethodAnnotation($beforeSendAnnotation);
                $beforeSendInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName()),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interceptorInterface),
                    $beforeSendInterceptor->precedence,
                    $beforeSendInterceptor->pointcut
                );
                $relatedInterfaces[] = InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName());
            }

            if ($interceptorInterface->hasMethodAnnotation($beforeAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeInterceptor = $interceptorInterface->getMethodAnnotation($beforeAnnotation);
                $preCallInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName()),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interceptorInterface),
                    $beforeInterceptor->precedence,
                    $beforeInterceptor->pointcut
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($afterAnnotation)) {
                /** @var After $afterInterceptor */
                $afterInterceptor = $interceptorInterface->getMethodAnnotation($afterAnnotation);
                $postCallInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    InterfaceToCall::create($methodInterceptor->getClassName(), $methodInterceptor->getMethodName()),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interceptorInterface),
                    $afterInterceptor->precedence,
                    $afterInterceptor->pointcut
                );
            }
        }

        return new self($beforeSendInterceptors, $preCallInterceptors, $aroundInterceptors, $postCallInterceptors, $relatedInterfaces);
    }

    /**
     * @param AnnotationRegistration $methodInterceptor
     * @param ParameterConverterAnnotationFactory $parameterConverterFactory
     * @param InterfaceToCall $interfaceToCall
     * @return MessageHandlerBuilderWithOutputChannel
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    private static function createMessageHandler(AnnotationRegistration $methodInterceptor, ParameterConverterAnnotationFactory $parameterConverterFactory, InterfaceToCall $interfaceToCall): MessageHandlerBuilderWithOutputChannel
    {
        /** @var After|Before $annotationForMethod */
        $annotationForMethod = $methodInterceptor->getAnnotationForMethod();
        $isTransformer = $annotationForMethod->changeHeaders;
        $parameterConverters = $annotationForMethod->parameterConverters;

        $methodParameterConverterBuilders = $parameterConverterFactory->createParameterConverters($interfaceToCall, $parameterConverters);
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
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->beforeSendInterceptors as $interceptor) {
            $configuration->registerBeforeSendInterceptor($interceptor);
        }
        $configuration->registerRelatedInterfaces($this->beforeSendRelatedInterfaces);
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