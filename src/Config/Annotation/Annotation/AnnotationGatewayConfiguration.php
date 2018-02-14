<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToPayloadAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToStaticHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToPayloadConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToStaticHeaderConverterBuilder;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="gateway-configuration")
 */
class AnnotationGatewayConfiguration implements AnnotationConfiguration
{
    /**
     * @var ConfigurationVariableRetrievingService
     */
    private $configurationVariableRetrievingService;

    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;

    /**
     * AnnotationGatewayConfiguration constructor.
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    private function __construct(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->configurationVariableRetrievingService = $configurationVariableRetrievingService;
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
    }

    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        return new self($configurationVariableRetrievingService, $classLocator, $classMetadataReader);
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        $annotationMessageEndpointConfigurationFinder = new AnnotationClassesWithMethodFinder($this->classLocator, $this->classMetadataReader);

        foreach ($annotationMessageEndpointConfigurationFinder->findFor(MessageEndpointAnnotation::class,GatewayAnnotation::class) as $annotationRegistration) {
            /** @var GatewayAnnotation $annotation */
            $annotation = $annotationRegistration->getAnnotation();

            $parameterConverters = [];
            foreach ($annotation->parameterConverters as $parameterToMessage) {
                if ($parameterToMessage instanceof ParameterToPayloadAnnotation) {
                    $parameterConverters[] = ParameterToPayloadConverterBuilder::create($parameterToMessage->parameterName);
                } else if ($parameterToMessage instanceof ParameterToHeaderAnnotation) {
                    $parameterConverters[] = ParameterToHeaderConverterBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName);
                } else if ($parameterToMessage instanceof ParameterToStaticHeaderAnnotation) {
                    $parameterConverters[] = ParameterToStaticHeaderConverterBuilder::create($parameterToMessage->headerName, $parameterToMessage->headerValue);
                }
            }

            $gateway = GatewayProxyBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getMessageEndpointClass(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                ->withMillisecondTimeout(1)
                ->withParameterToMessageConverters($parameterConverters);

            $configuration->registerGatewayBuilder($gateway);
        }
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}