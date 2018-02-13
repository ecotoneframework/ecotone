<?php

namespace Fixture\Annotation\MessageEndpoint\Router;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\RouterAnnotation;

/**
 * Class RouterWithNoResolutionRequiredExample
 * @package Fixture\Annotation\MessageEndpoint\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class RouterWithNoResolutionRequiredExample
{
    /**
     * @param $content
     * @return string
     * @RouterAnnotation(inputChannel="inputChannel", isResolutionRequired=false, parameterConverters={
     *     @MessageToPayloadParameterAnnotation(parameterName="content")
     * })
     */
    public function route($content) : string
    {
        return "outputChannel";
    }
}