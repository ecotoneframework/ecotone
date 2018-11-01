<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

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
     */
    public $propertyPath;
    /**
     * @var string
     */
    public $expression;
    /**
     * @var string
     */
    public $nullResultExpression;
}