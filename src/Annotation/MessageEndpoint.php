<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

/**
 * Class MessageEndpoint
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MessageEndpoint
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}