<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class PayloadParameterConverter
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
class Payload
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}