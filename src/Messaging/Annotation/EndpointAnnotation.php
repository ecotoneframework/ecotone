<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

/**
 * Class EndpointAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class EndpointAnnotation extends IdentifiedAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannelName;

    public function __construct(array $values = [])
    {
        if (isset($values['value'])) {
            $this->inputChannelName = $values['value'];
        }

        parent::__construct($values);
    }
}