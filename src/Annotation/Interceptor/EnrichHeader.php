<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class EnrichHeaderWithExpression
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class EnrichHeader
{
    /**
     * @var string
     * @Required()
     */
    public $propertyPath;
    /**
     * @var string
     */
    public $expression = "";
    /**
     * @var string
     */
    public $value = "";
    /**
     * @var string
     */
    public $nullResultExpression = "";
}