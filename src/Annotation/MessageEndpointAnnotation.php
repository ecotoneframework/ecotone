<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageEndpoint
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class MessageEndpointAnnotation
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}