<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

/**
 * Class EnricherInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class EnricherInterceptor
{
    /**
     * @var string
     */
    public $requestMessageChannel = "";
    /**
     * @var string
     */
    public $requestPayloadExpression = "";
    /**
     * @var array
     */
    public $requestHeaders = [];
    /**
     * @var array
     */
    public $editors;
}