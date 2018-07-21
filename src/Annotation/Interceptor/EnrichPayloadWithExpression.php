<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

/**
 * Class EnrichPayloadWithExpression
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class EnrichPayloadWithExpression
{
    /**
     * @var string
     */
    public $propertyPath;
    /**
     * @var string
     */
    public $expression;
}