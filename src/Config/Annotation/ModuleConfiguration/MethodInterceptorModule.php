<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\InputOutputEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ClassInterceptors;
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
     * @var array|MessageHandlerBuilderWithOutputChannel[]
     */
    private $postCallInterceptors;
    /**
     * @var array|MessageHandlerBuilderWithOutputChannel[]
     */
    private $preCallInterceptors;

    /**
     * MethodInterceptorModule constructor.
     * @param MessageHandlerBuilderWithOutputChannel[] $preCallInterceptors
     * @param MessageHandlerBuilderWithOutputChannel[] $postCallInterceptors
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
        foreach ($interceptorsToConvert as $interceptor) {
            switch (get_class($interceptor)) {
                case ServiceActivatorInterceptor::class: {
                    $interceptors[] = ServiceActivatorBuilder::create($interceptor->referenceName, $interceptor->methodName)
                        ->withEndpointId($endpointId)
                        ->withMethodParameterConverters(
                            $parameterConverterFactory->createParameterConverters(
                                null, $interceptor->parameterConverters
                            )
                        );
                }
            }
        }

        return $interceptors;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions): void
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
    public function getName(): string
    {
        return self::MODULE_NAME;
    }
}