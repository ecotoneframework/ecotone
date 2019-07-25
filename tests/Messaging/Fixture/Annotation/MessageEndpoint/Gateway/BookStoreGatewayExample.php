<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeaderValue;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\HeaderExpression;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\HeaderValue;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;

/**
 * Interface GatewayWithAllConvertersExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface BookStoreGatewayExample
{
    /**
     * @param string $bookNumber
     * @param string $rentTill
     * @param int $cost
     * @param array $data
     *
     * @return bool
     *
     * @Gateway(
     *      requestChannel="requestChannel",
     *      errorChannel="errorChannel",
     *      parameterConverters={
     *          @Payload(parameterName="bookNumber", expression="upper(value)"),
     *          @Header(parameterName="rentTill", headerName="rentDate"),
     *          @Header(parameterName="cost", headerName="cost"),
     *          @Headers(parameterName="data"),
     *          @HeaderValue(headerName="secret", headerValue="123")
     *      },
     *      requiredInterceptorNames={"dbalTransaction"},
     *      replyTimeoutInMilliseconds=100
     * )
     */
    public function rent(string $bookNumber, string $rentTill, int $cost, array $data): bool;
}