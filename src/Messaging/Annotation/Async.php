<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class Async
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Async
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannelName;
}