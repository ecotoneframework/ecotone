<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;

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
     *          @Payload(parameterName="orderId")
     *      },
     *      requiredInterceptorNames={"dbalTransaction"}
     * )
     */
    public function buy(string $orderId): bool;
}