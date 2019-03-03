<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\InputOutputEndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ClassInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnrichHeader;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnrichPayload;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\GatewayInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptorAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Config\OrderedMethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichPayloadWithExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\Converter\EnrichPayloadWithValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
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
    public static function create(AnnotationRegistrationService $annotationRegistrationService): MethodInterceptorModule
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
                    $postCallInterceptors = array_merge($postCallInterceptors, self::createInterceptorList($endpointAnnotation->endpointId, false, $methodInterceptors, $parameterConverterFactory));
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
                $preCallInterceptors = array_merge($preCallInterceptors, self::createInterceptorList($relatedEndpointId, true, $methodInterceptorsRegistration->getAnnotationForMethod(), $parameterConverterFactory));
                $postCallInterceptors = array_merge($postCallInterceptors, self::createInterceptorList($relatedEndpointId, false, $methodInterceptorsRegistration->getAnnotationForMethod(), $parameterConverterFactory));
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
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
            } else if ($interceptor instanceof EnricherInterceptor) {
                $propertyEditors = [];
                foreach ($interceptor->editors as $editor) {
                    if ($editor instanceof EnrichHeader) {
                        if ($editor->expression) {
                            $propertyEditors[] = EnrichHeaderWithExpressionBuilder::createWith($editor->propertyPath, $editor->expression)
                                ->withNullResultExpression($editor->nullResultExpression);
                        } else {
                            $propertyEditors[] = EnrichHeaderWithValueBuilder::create($editor->propertyPath, $editor->value);
                        }
                    } else if ($editor instanceof EnrichPayload) {
                        if ($editor->expression) {
                            if ($editor->mappingExpression) {
                                $propertyEditors[] = EnrichPayloadWithExpressionBuilder::createWithMapping($editor->propertyPath, $editor->expression, $editor->mappingExpression)
                                    ->withNullResultExpression($editor->nullResultExpression);
                            } else if ($editor->expression) {
                                $propertyEditors[] = EnrichPayloadWithExpressionBuilder::createWith($editor->propertyPath, $editor->expression)
                                    ->withNullResultExpression($editor->nullResultExpression);
                            }
                        } else if ($editor->value) {
                            $propertyEditors[] = EnrichPayloadWithValueBuilder::createWith($editor->propertyPath, $editor->value);
                        }
                    } else {
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
            } else if ($interceptor instanceof GatewayInterceptor) {
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