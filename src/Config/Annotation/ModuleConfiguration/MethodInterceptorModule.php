<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\InputOutputEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ClassInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ClassMethodInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class MethodInterceptorModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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
        $classMethodInterceptors = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, ClassInterceptors::class);
        $multipleMethodsInterceptors = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, MethodInterceptors::class);
        $endpoints = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, InputOutputEndpointAnnotation::class);;

        $preCallInterceptors = [];
        $postCallInterceptors = [];

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
                if (!$relatedEndpointId) {
                    throw ConfigurationException::create("EndpointId must not be empty for {$methodInterceptorsRegistration->getClassName()}:{$methodInterceptorsRegistration->getMethodName()}. If method interceptors are used it must be defined");
                }

                $preCallInterceptors = self::createInterceptorList($relatedEndpointId, true, $methodInterceptorsRegistration, $parameterConverterFactory);
                $postCallInterceptors = self::createInterceptorList($relatedEndpointId,false, $methodInterceptorsRegistration, $parameterConverterFactory);
            }
        }

        return new self($preCallInterceptors, $postCallInterceptors);
    }

    /**
     * @param string $endpointId
     * @param bool $forPreCall
     * @param AnnotationRegistration $annotationRegistration
     * @param ParameterConverterAnnotationFactory $parameterConverterFactory
     * @return array|MessageHandlerBuilderWithOutputChannel[]
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    private static function createInterceptorList(string $endpointId, bool $forPreCall, AnnotationRegistration $annotationRegistration, ParameterConverterAnnotationFactory $parameterConverterFactory): array
    {
        /** @var MethodInterceptors $methodInterceptors */
        $methodInterceptors = $annotationRegistration->getAnnotationForMethod();
        Assert::isTrue($methodInterceptors instanceof MethodInterceptors, "Annotation must be MethodInterceptor");

        $interceptors = [];
        $interceptorsToConvert = $forPreCall ? $methodInterceptors->preCallInterceptors : $methodInterceptors->postCallInterceptors;
        foreach ($interceptorsToConvert as $interceptor) {
            switch (get_class($interceptor)) {
                case ServiceActivatorInterceptor::class: {
                    $interceptors[] = ServiceActivatorBuilder::create($interceptor->referenceName, $interceptor->methodName)
                        ->withEndpointId($endpointId)
                        ->withMethodParameterConverters($parameterConverterFactory->createParameterConverters($annotationRegistration->getClassName(), $annotationRegistration->getMethodName(), $interceptor->parameterConverters));
                }
            }
        }

        return $interceptors;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver): void
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