<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;
use Fixture\Annotation\Interceptor\EnrichInterceptorExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\InputOutputEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ClassInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnrichHeader;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnrichPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\GatewayInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\OrderedMethodInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter\EnrichPayloadWithExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter\EnrichPayloadWithValueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class MethodInterceptorModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class MethodInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "methodInterceptorModule";
    /**
     * @var array|OrderedMethodInterceptor[]
     */
    private $postCallInterceptors;
    /**
     * @var array|OrderedMethodInterceptor[]
     */
    private $preCallInterceptors;

    /**
     * MethodInterceptorModule constructor.
     * @param OrderedMethodInterceptor[] $preCallInterceptors
     * @param OrderedMethodInterceptor[] $postCallInterceptors
     */
    private function __construct(array $preCallInterceptors, array $postCallInterceptors)
    {
        $this->preCallInterceptors = $preCallInterceptors;
        $this->postCallInterceptors = $postCallInterceptors;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService) : MethodInterceptorModule
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        $multipleClassInterceptors = $annotationRegistrationService->getAllClassesWithAnnotation(ClassInterceptors::class);
        $multipleMethodsInterceptors = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, MethodInterceptors::class);
        $endpoints = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, InputOutputEndpointAnnotation::class);

        $preCallInterceptors = [];
        $postCallInterceptors = [];
        foreach ($endpoints as $endpoint) {
            if (in_array($endpoint->getClassName(), $multipleClassInterceptors)) {
                /** @var EndpointAnnotation $endpointAnnotation */
                $endpointAnnotation = $endpoint->getAnnotationForMethod();
                /** @var ClassInterceptors $classInterceptors */
                $classInterceptors = $annotationRegistrationService->getAnnotationForClass($endpoint->getClassName(), ClassInterceptors::class);

                /** @var MethodInterceptors $methodInterceptors */
                foreach ($classInterceptors->classMethodsInterceptors as $methodInterceptors) {
                    if (in_array($endpoint->getMethodName(), $methodInterceptors->excludedMethods)) {
                        continue;
                    }

                    $preCallInterceptors = array_merge($preCallInterceptors, self::createInterceptorList($endpointAnnotation->endpointId, true, $methodInterceptors, $parameterConverterFactory));
                    $postCallInterceptors = array_merge($postCallInterceptors, self::createInterceptorList($endpointAnnotation->endpointId,false, $methodInterceptors, $parameterConverterFactory));
                }
            }
        }

        foreach ($multipleMethodsInterceptors as $methodInterceptorsRegistration) {
            $relatedEndpointIds = [];
            foreach ($endpoints as $endpoint) {
                if ($endpoint->getClassName() === $methodInterceptorsRegistration->getClassName()) {
                    /** @var EndpointAnnotation $endpointAnnotation */
                    $endpointAnnotation = $endpoint->getAnnotationForMethod();

                    $relatedEndpointIds[] = $endpointAnnotation->endpointId;
                    break;
                }
            }

            if (empty($relatedEndpointIds)) {
                throw ConfigurationException::create("No endpointId configuration defined for {$methodInterceptorsRegistration->getClassName()}:{$methodInterceptorsRegistration->getMethodName()}. If method interceptors are used it must be defined");
            }

            foreach ($relatedEndpointIds as $relatedEndpointId) {
                $preCallInterceptors = array_merge($preCallInterceptors, self::createInterceptorList($relatedEndpointId,true, $methodInterceptorsRegistration->getAnnotationForMethod(), $parameterConverterFactory));
                $postCallInterceptors = array_merge($postCallInterceptors, self::createInterceptorList($relatedEndpointId,false, $methodInterceptorsRegistration->getAnnotationForMethod(), $parameterConverterFactory));
            }
        }

        return new self($preCallInterceptors, $postCallInterceptors);
    }

    /**
     * @param string $endpointId
     * @param bool $forPreCall
     * @param MethodInterceptors $methodInterceptors
     * @param ParameterConverterAnnotationFactory $parameterConverterFactory
     * @return array|MessageHandlerBuilderWithOutputChannel[]
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    private static function createInterceptorList(string $endpointId, bool $forPreCall, MethodInterceptors $methodInterceptors, ParameterConverterAnnotationFactory $parameterConverterFactory): array
    {
        Assert::isTrue($methodInterceptors instanceof MethodInterceptors, "Annotation must be MethodInterceptor");

        $interceptors = [];
        $interceptorsToConvert = $forPreCall ? $methodInterceptors->preCallInterceptors : $methodInterceptors->postCallInterceptors;
        /** @var MethodInterceptorAnnotation $interceptor */
        foreach ($interceptorsToConvert as $interceptor) {
            if ($interceptor instanceof ServiceActivatorInterceptor) {
                $interceptors[] =
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::create($interceptor->referenceName, $interceptor->methodName)
                            ->withEndpointId($endpointId)
                            ->withMethodParameterConverters(
                                $parameterConverterFactory->createParameterConverters(
                                    null, $interceptor->parameterConverters
                                )
                        ),
                        $interceptor->weightOrder
                    );
            }else if ($interceptor instanceof EnricherInterceptor) {
                $propertyEditors = [];
                foreach ($interceptor->editors as $editor) {
                    if ($editor instanceof EnrichHeader) {
                        if ($editor->expression) {
                            $propertyEditors[] = EnrichHeaderWithExpressionBuilder::createWith($editor->propertyPath, $editor->expression)
                                ->withNullResultExpression($editor->nullResultExpression);
                        }else {
                            $propertyEditors[] = EnrichHeaderWithValueBuilder::create($editor->propertyPath, $editor->value);
                        }
                    }else if ($editor instanceof EnrichPayload) {
                        if ($editor->expression) {
                            if ($editor->mappingExpression) {
                                $propertyEditors[] = EnrichPayloadWithExpressionBuilder::createWithMapping($editor->propertyPath, $editor->expression, $editor->mappingExpression)
                                    ->withNullResultExpression($editor->nullResultExpression);
                            }else if ($editor->expression){
                                $propertyEditors[] = EnrichPayloadWithExpressionBuilder::createWith($editor->propertyPath, $editor->expression)
                                    ->withNullResultExpression($editor->nullResultExpression);
                            }
                        }else if ($editor->value) {
                            $propertyEditors[] = EnrichPayloadWithValueBuilder::createWith($editor->propertyPath, $editor->value);
                        }
                    }else {
                        $className = get_class($editor);
                        throw ConfigurationException::create("Registered not known property editor {$className} for EnricherInterceptor");
                    }
                }

                $interceptors[] =
                    OrderedMethodInterceptor::create(
                        EnricherBuilder::create($propertyEditors)
                            ->withEndpointId($endpointId)
                            ->withRequestHeaders($interceptor->requestHeaders)
                            ->withRequestPayloadExpression($interceptor->requestPayloadExpression)
                            ->withRequestMessageChannel($interceptor->requestMessageChannel),
                        $interceptor->weightOrder
                    );
            }else if ($interceptor instanceof GatewayInterceptor) {
                $interceptors[] =
                    OrderedMethodInterceptor::create(
                        GatewayInterceptorBuilder::create($interceptor->requestChannelName)
                            ->withEndpointId($endpointId),
                        $interceptor->weightOrder
                    );
            }
        }

        return $interceptors;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects): void
    {
        foreach ($this->preCallInterceptors as $preCallInterceptor) {
            $configuration->registerPreCallMethodInterceptor($preCallInterceptor);
        }
        foreach ($this->postCallInterceptors as $postCallInterceptor) {
            $configuration->registerPostCallMethodInterceptor($postCallInterceptor);
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