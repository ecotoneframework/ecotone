<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\Presend;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Attribute\IgnorePayload;

#[ModuleAnnotation]
class MethodInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "methodInterceptorModule";
    private array $postCallInterceptors;
    private array $preCallInterceptors;
    private array $aroundInterceptors;
    private array $beforeSendInterceptors;
    private array $beforeSendRelatedInterfaces = [];

    /**
     * MethodInterceptorModule constructor.
     *
     * @param MethodInterceptor[]          $beforeSendInterceptors
     * @param MethodInterceptor[]          $preCallInterceptors
     * @param AroundInterceptorReference[] $aroundInterceptors
     * @param MethodInterceptor[]          $postCallInterceptors
     * @param array                        $relatedInterfaces
     */
    private function __construct(array $beforeSendInterceptors, array $preCallInterceptors, array $aroundInterceptors, array $postCallInterceptors, array $relatedInterfaces)
    {
        $this->preCallInterceptors         = $preCallInterceptors;
        $this->postCallInterceptors        = $postCallInterceptors;
        $this->aroundInterceptors          = $aroundInterceptors;
        $this->beforeSendInterceptors      = $beforeSendInterceptors;
        $this->beforeSendRelatedInterfaces = $relatedInterfaces;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        /** @var AnnotatedFinding[] $methodsInterceptors */
        $methodsInterceptors = array_merge(
            $annotationRegistrationService->findAnnotatedMethods(Presend::class),
            $annotationRegistrationService->findAnnotatedMethods(Before::class),
            $annotationRegistrationService->findAnnotatedMethods(Around::class),
            $annotationRegistrationService->findAnnotatedMethods(After::class)
        );

        $beforeSendAnnotation = TypeDescriptor::create(Presend::class);
        $beforeAnnotation     = TypeDescriptor::create(Before::class);
        $aroundAnnotation     = TypeDescriptor::create(Around::class);
        $afterAnnotation      = TypeDescriptor::create(After::class);

        $beforeSendInterceptors = [];
        $relatedInterfaces      = [];
        $preCallInterceptors    = [];
        $aroundInterceptors     = [];
        $postCallInterceptors   = [];
        foreach ($methodsInterceptors as $methodInterceptor) {
            $interceptorInterface = $interfaceToCallRegistry->getFor($methodInterceptor->getClassName(), $methodInterceptor->getMethodName());

            if ($interceptorInterface->hasMethodAnnotation($aroundAnnotation)) {
                /** @var Around $aroundInterceptor */
                $aroundInterceptor    = $interceptorInterface->getMethodAnnotation($aroundAnnotation);
                $aroundInterceptors[] = AroundInterceptorReference::create(
                    $methodInterceptor->getClassName(),
                    AnnotatedDefinitionReference::getReferenceFor($methodInterceptor),
                    $methodInterceptor->getMethodName(),
                    $aroundInterceptor->precedence,
                    $aroundInterceptor->pointcut,
                    $parameterConverterFactory->createParameterConverters($interceptorInterface)
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($beforeSendAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeSendInterceptor    = $interceptorInterface->getMethodAnnotation($beforeSendAnnotation);
                $beforeSendInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    AnnotatedDefinitionReference::getReferenceFor($methodInterceptor),
                    $interceptorInterface,
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interceptorInterface),
                    $beforeSendInterceptor->precedence,
                    $beforeSendInterceptor->pointcut
                );
                $relatedInterfaces[]      = $interfaceToCallRegistry->getFor($methodInterceptor->getClassName(), $methodInterceptor->getMethodName());
            }

            if ($interceptorInterface->hasMethodAnnotation($beforeAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeInterceptor     = $interceptorInterface->getMethodAnnotation($beforeAnnotation);
                $preCallInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    AnnotatedDefinitionReference::getReferenceFor($methodInterceptor),
                    $interceptorInterface,
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interceptorInterface),
                    $beforeInterceptor->precedence,
                    $beforeInterceptor->pointcut
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($afterAnnotation)) {
                /** @var After $afterInterceptor */
                $afterInterceptor       = $interceptorInterface->getMethodAnnotation($afterAnnotation);
                $postCallInterceptors[] = \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    AnnotatedDefinitionReference::getReferenceFor($methodInterceptor),
                    $interceptorInterface,
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interceptorInterface),
                    $afterInterceptor->precedence,
                    $afterInterceptor->pointcut
                );
            }
        }

        return new self($beforeSendInterceptors, $preCallInterceptors, $aroundInterceptors, $postCallInterceptors, $relatedInterfaces);
    }

    private static function createMessageHandler(AnnotatedFinding $methodInterceptor, ParameterConverterAnnotationFactory $parameterConverterFactory, InterfaceToCall $interfaceToCall): MessageHandlerBuilderWithOutputChannel
    {
        /** @var After|Before $annotationForMethod */
        $annotationForMethod = $methodInterceptor->getAnnotationForMethod();
        $isTransformer       = $annotationForMethod->changeHeaders;

        $methodParameterConverterBuilders = $parameterConverterFactory->createParameterWithDefaults($interfaceToCall, (bool)$interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(IgnorePayload::class)));
        if ($isTransformer) {
            return TransformerBuilder::create(AnnotatedDefinitionReference::getReferenceFor($methodInterceptor), $methodInterceptor->getMethodName())
                ->withMethodParameterConverters($methodParameterConverterBuilders);
        }

        return ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($methodInterceptor), $methodInterceptor->getMethodName())
            ->withPassThroughMessageOnVoidInterface(true)
            ->withMethodParameterConverters($methodParameterConverterBuilders);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
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
}