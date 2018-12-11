<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Transformer;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class TransformerModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = "transformerModule";

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var Transformer $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return TransformerBuilder::create(
            $annotationRegistration->getReferenceName(),
            $annotationRegistration->getMethodName()
        )
            ->withEndpointId("some-id")
            ->withInputChannelName($annotation->inputChannelName)
            ->withOutputMessageChannel($annotation->outputChannelName);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Transformer::class;
    }
}