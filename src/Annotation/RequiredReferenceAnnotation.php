<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class RequiredReferenceAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class RequiredReferenceAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $referenceName;
    /**
     * @var string
     * @Required()
     */
    public $className;
    /**
     * @var string
     * @Required()
     */
    public $description;
}