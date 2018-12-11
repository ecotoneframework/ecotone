<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Environment
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 * @Target({"CLASS", "METHOD"})
 */
class Environment
{
    /**
     * @var string[]
     */
    public $names = [];
}