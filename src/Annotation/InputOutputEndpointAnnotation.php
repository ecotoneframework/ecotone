<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

/**
 * Class InputOutputEndpointAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InputOutputEndpointAnnotation extends EndpointAnnotation
{
    /**
     * @var string
     */
    public $outputChannelName = '';
}