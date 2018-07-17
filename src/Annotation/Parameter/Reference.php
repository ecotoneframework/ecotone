<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class ReferenceServiceParameterConverterAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Reference
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     */
    public $referenceName = '';
}