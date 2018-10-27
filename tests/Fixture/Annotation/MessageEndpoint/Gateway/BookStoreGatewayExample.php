<?php
declare(strict_types=1);

namespace Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayHeaderValue;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;

/**
 * Interface GatewayWithAllConvertersExample
 * @package Fixture\Annotation\MessageEndpoint\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface BookStoreGatewayExample
{
    /**
     * @param string $bookNumber
     * @param string $rentTill
     * @param int $cost
     * @return bool
     *
     * @Gateway(
     *      requestChannel="requestChannel",
     *      errorChannel="errorChannel",
     *      parameterConverters={
     *          @GatewayPayload(parameterName="bookNumber", expression="upper(value)"),
     *          @GatewayHeader(parameterName="rentTill", headerName="rentDate"),
     *          @GatewayHeader(parameterName="cost", headerName="cost", expression="value * 5"),
     *          @GatewayHeaderValue(headerName="owner", headerValue="Johny")
     *      },
     *      transactionFactories={"dbalTransaction"}
     * )
     */
    public function rent(string $bookNumber, string $rentTill, int $cost): bool;
}