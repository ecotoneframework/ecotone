<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage\ParameterToHeaderAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage\ParameterToPayloadAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage\ParameterToStaticHeaderAnnotation;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\ParameterToPayloadConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\ParameterToStaticHeaderConverterBuilder;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
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