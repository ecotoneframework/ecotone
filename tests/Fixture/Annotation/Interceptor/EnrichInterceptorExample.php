<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnrichHeader;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnrichPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class EnrichInterceptorExample
 * @package Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class EnrichInterceptorExample
{
    /**
     * @ServiceActivator(endpointId="some-id")
     * @MethodInterceptors(
     *     preCallInterceptors={
     *          @EnricherInterceptor(
     *              requestMessageChannel="requestChannel",
     *              requestPayloadExpression="payload['name']",
     *              requestHeaders={
     *                  "token"="1234"
     *              },
     *              editors={
     *                  @EnrichPayload(propertyPath="orders[*][person]", expression="payload", mappingExpression="requestContext['personId'] == replyContext['personId']", nullResultExpression="reference('fakeData').get()"),
     *                  @EnrichPayload(propertyPath="session1", expression="'some1'"),
     *                  @EnrichPayload(propertyPath="session2", value="some2"),
     *                  @EnrichHeader(propertyPath="token1", expression="'123'", nullResultExpression="'1234'"),
     *                  @EnrichHeader(propertyPath="token2", value="1234")
     *              }
     *          )
     *     }
     * )
     */
    public function execute() : void
    {

    }
}