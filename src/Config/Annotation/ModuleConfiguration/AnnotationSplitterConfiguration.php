<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\SplitterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\TransformerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter\SplitterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="splitterConfiguration")
 */
class AnnotationSplitterConfiguration extends MessageHandlerRegisterConfiguration
{
     /**
     * @inheritDoc
     */
    public function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var TransformerAnnotation $annotation */
        $annotation = $annotationRegistration->getAnnotation();

        return
            SplitterBuilder::create(
                $annotation->inputChannelName,
                $annotationRegistration->getReferenceName(),
                $annotationRegistration->getMethodName()
            )
            ->withConsumerName($annotationRegistration->getReferenceName())
            ->withOutputChannel($annotation->outputChannelName)
        ;
    }

    /**
     * @inheritDoc
     */
    public function getMessageHandlerAnnotation(): string
    {
        return SplitterAnnotation::class;
    }
}