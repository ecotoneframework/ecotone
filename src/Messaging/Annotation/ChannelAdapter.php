<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

/**
 * Class EndpointAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class ChannelAdapter extends IdentifiedAnnotation
{
    /**
     * @var Poller|null
     */
    public $poller;
}