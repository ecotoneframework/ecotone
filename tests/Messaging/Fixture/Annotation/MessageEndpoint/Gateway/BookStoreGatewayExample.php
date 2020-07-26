<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Gateway\GatewayHeaderValue;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\HeaderExpression;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\HeaderValue;
use Ecotone\Messaging\Annotation\Parameter\Payload;

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
     * @MessageGateway(
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