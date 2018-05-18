<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class GatewayAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class GatewayAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $requestChannel;
    /**
     * @var string
     */
    public $errorChannel = "";
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * @var array
     */
    public $transactionFactories = [];
}