<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Environment
 * @package Ecotone\Messaging\Annotation
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