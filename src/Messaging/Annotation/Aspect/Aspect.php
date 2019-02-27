<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Aspect;

/**
 * Class Annotation
 * @package SimplyCodedSoftware\Messaging\Annotation\Aspect
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Aspect
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}