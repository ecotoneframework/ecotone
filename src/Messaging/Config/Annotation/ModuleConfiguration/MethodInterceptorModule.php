<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\After;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Before;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnrichHeader;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnrichPayload;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\GatewayInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptorAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\TransformerInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichPayloadWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichPayloadWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class MethodInterceptorModule
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration
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
     * MethodInterceptorModule constructor.
     * @param MethodInterceptor[] $preCallInterceptors
     * @param AroundInterceptorReference[] $aroundInterceptors
     * @param MethodInterceptor[] $postCallInterceptors
     */
    private function __construct(array $preCallInterceptors, array $aroundInterceptors, array $postCallInterceptors)
    {
        $this->preCallInterceptors = $preCallInterceptors;
        $this->postCallInterceptors = $postCallInterceptors;
        $this->aroundInterceptors = $aroundInterceptors;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): MethodInterceptorModule
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        /** @var AnnotationRegistration[] $methodsInterceptors */
        $methodsInterceptors = array_merge(
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Before::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, Around::class),
            $annotationRegistrationService->findRegistrationsFor(MethodInterceptor::class, After::class)
        );

        $beforeAnnotation = TypeDescriptor::create(Before::class);
        $aroundAnnotation = TypeDescriptor::create(Around::class);
        $afterAnnotation = TypeDescriptor::create(After::class);

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
                    $methodInterceptor->getMethodName(),
                    $aroundInterceptor->precedence,
                    $aroundInterceptor->pointcut
                );
            }

            if ($interfaceToCall->hasMethodAnnotation($beforeAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeInterceptor = $interfaceToCall->getMethodAnnotation($beforeAnnotation);
                $preCallInterceptors[] = \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interfaceToCall),
                    $beforeInterceptor->precedence,
                    $beforeInterceptor->pointcut
                );
            }
            if ($interfaceToCall->hasMethodAnnotation($afterAnnotation)) {
                /** @var After $afterInterceptor */
                $afterInterceptor = $interfaceToCall->getMethodAnnotation($afterAnnotation);
                $postCallInterceptors[] = \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    $methodInterceptor->getReferenceName(),
                    self::createMessageHandler($methodInterceptor, $parameterConverterFactory, $interfaceToCall),
                    $afterInterceptor->precedence,
                    $afterInterceptor->pointcut
                );
            }
        }

        return new self($preCallInterceptors, $aroundInterceptors, $postCallInterceptors);
    }

    /**
     * @param AnnotationRegistration $methodInterceptor
     * @param ParameterConverterAnnotationFactory $parameterConverterFactory
     * @param InterfaceToCall $interfaceToCall
     * @return MessageHandlerBuilderWithOutputChannel
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    private static function createMessageHandler(AnnotationRegistration $methodInterceptor, ParameterConverterAnnotationFactory $parameterConverterFactory, InterfaceToCall $interfaceToCall): MessageHandlerBuilderWithOutputChannel
    {
        $serviceActivatorInterceptorAnnotation = TypeDescriptor::create(ServiceActivatorInterceptor::class);
        $transformerInterceptorAnnotation = TypeDescriptor::create(TransformerInterceptor::class);

        if ($interfaceToCall->hasMethodAnnotation($serviceActivatorInterceptorAnnotation)) {
            /** @var ServiceActivatorInterceptor $interceptorAnnotation */
            $interceptorAnnotation = $interfaceToCall->getMethodAnnotation($serviceActivatorInterceptorAnnotation);
            $messageHandler = ServiceActivatorBuilder::create($methodInterceptor->getReferenceName(), $methodInterceptor->getMethodName())
                ->withMethodParameterConverters($parameterConverterFactory->createParameterConverters(
                    $interfaceToCall, $interceptorAnnotation->parameterConverters
                ));

            return $messageHandler;
        }

        if ($interfaceToCall->hasMethodAnnotation($transformerInterceptorAnnotation)) {
            /** @var TransformerInterceptor $interceptorAnnotation */
            $interceptorAnnotation = $interfaceToCall->getMethodAnnotation($transformerInterceptorAnnotation);
            $messageHandler = TransformerBuilder::create($methodInterceptor->getReferenceName(), $methodInterceptor->getMethodName())
                ->withMethodParameterConverters($parameterConverterFactory->createParameterConverters(
                    $interfaceToCall, $interceptorAnnotation->parameterConverters
                ));

            return $messageHandler;
        }

        throw ConfigurationException::create("Missing Interceptor Handler Annotation for {$interfaceToCall}");
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects): void
    {
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