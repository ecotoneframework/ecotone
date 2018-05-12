<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToMessage;

/**
 * Class MessageToSetterExpressionAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToMessage
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MessageToMessagePayloadExpressionSetterAnnotation
{
    /**
     * @var string
     */
    public $propertyPathToSet;
    /**
     * @var string
     */
    public $expression;
}