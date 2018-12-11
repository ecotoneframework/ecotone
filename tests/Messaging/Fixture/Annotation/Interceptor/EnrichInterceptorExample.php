<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnrichHeader;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\EnrichPayload;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;

/**
 * Class EnrichInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor
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