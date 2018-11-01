<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

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
     */
    public $propertyPath;
    /**
     * @var string
     */
    public $expression;
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
    public $nullResultExpression;
}