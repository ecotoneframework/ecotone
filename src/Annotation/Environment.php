<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Environment
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
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