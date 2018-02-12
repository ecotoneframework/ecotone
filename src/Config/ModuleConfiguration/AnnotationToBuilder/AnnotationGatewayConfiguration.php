<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class AnnotationGatewayConfiguration extends MessageHandlerRegisterConfiguration
{
    /**
     * @inheritDoc
     */
    public function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        // TODO: Implement createMessageHandlerFrom() method.
    }

    /**
     * @inheritDoc
     */
    public function getMessageHandlerAnnotation(): string
    {
        // TODO: Implement getMessageHandlerAnnotation() method.
    }
}