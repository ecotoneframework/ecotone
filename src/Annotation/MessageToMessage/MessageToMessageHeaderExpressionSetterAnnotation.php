<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToMessage;

/**
 * Class MessageToMessageHeaderExpressionSetterAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToMessage
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MessageToMessageHeaderExpressionSetterAnnotation
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