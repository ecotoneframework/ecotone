<?php
declare(strict_types=1);

namespace Fixture\Annotation\MessageEndpoint\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;

/**
 * Class GatewayWithReplyChannelExample
 * @package Fixture\Annotation\MessageEndpoint\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface GatewayWithReplyChannelExample
{
    /**
     * @param string $orderId
     * @return bool
     *
     * @Gateway(
     *      requestChannel="requestChannel",
     *      parameterConverters={
     *          @GatewayPayload(parameterName="orderId")
     *      },
     *      transactionFactories={"dbalTransaction"}
     * )
     */
    public function buy(string $orderId): bool;
}