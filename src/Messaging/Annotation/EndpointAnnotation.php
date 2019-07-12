<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;
use Ramsey\Uuid\Uuid;

/**
 * Class EndpointAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class EndpointAnnotation
{
    /**
     * @var string
     */
    public $endpointId;
    /**
     * @var Poller|null
     */
    public $poller;

    /**
     * EndpointAnnotation constructor.
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $propertyName => $value) {
            $this->{$propertyName} = $value;
        }

        if (!$this->endpointId) {
            $this->endpointId = Uuid::uuid4()->toString();
        }
    }
}