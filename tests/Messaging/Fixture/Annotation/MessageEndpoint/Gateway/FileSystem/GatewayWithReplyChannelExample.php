<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class GatewayWithReplyChannelExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway
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
     *      requiredInterceptorNames={"dbalTransaction"}
     * )
     */
    public function buy(string $orderId): bool;
}