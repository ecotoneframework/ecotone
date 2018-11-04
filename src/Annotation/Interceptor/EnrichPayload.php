<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class EnrichPayloadWithExpression
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class EnrichPayload
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
     * Allow for enriching multiple elements at once, using mapping between request and reply messages
     * Property path points to specific place to enrich in array context e.g. [orders][*][person]
     * Expression evaluates to array, that will be mapped
     *
     * @var string How to map between request and reply requestContext['personId'] == replyContext['personId']
     */
    public $mappingExpression;
    /**
     * @var string
     */
    public $nullResultExpression = "";
}