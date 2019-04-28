<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeaderArray;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeaderValue;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

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
     * @param int    $cost
     * @param array  $data
     *
     * @return bool
     *
     * @Gateway(
     *      requestChannel="requestChannel",
     *      errorChannel="errorChannel",
     *      parameterConverters={
     *          @GatewayPayload(parameterName="bookNumber", expression="upper(value)"),
     *          @GatewayHeader(parameterName="rentTill", headerName="rentDate"),
     *          @GatewayHeader(parameterName="cost", headerName="cost", expression="value * 5"),
     *          @GatewayHeaderValue(headerName="owner", headerValue="Johny"),
     *          @GatewayHeaderArray(parameterName="data")
     *      },
     *      requiredInterceptorNames={"dbalTransaction"},
     *      replyTimeoutInMilliseconds=100
     * )
     */
    public function rent(string $bookNumber, string $rentTill, int $cost, array $data): bool;
}