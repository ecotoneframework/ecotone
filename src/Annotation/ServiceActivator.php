<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ServiceActivator
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class ServiceActivator extends EndpointAnnotation
{
    /**
     * @var string
     */
    public $outputChannelName = '';
    /**
     * @var bool
     */
    public $requiresReply = false;
    /**
     * @var array
     */
    public $parameterConverters = [];
}