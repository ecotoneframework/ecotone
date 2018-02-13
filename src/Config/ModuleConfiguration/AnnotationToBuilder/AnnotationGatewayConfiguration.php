<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToPayloadAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToStaticHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToPayloadConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToStaticHeaderConverterBuilder;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class AnnotationGatewayConfiguration extends BaseAnnotationConfiguration
{
    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration): void
    {
        $annotationMessageEndpointConfigurationFinder = new AnnotationClassesWithMethodFinder($this->classLocator, $this->classMetadataReader);

        foreach ($annotationMessageEndpointConfigurationFinder->findFor(MessageEndpoint::class,GatewayAnnotation::class) as $annotationRegistration) {
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