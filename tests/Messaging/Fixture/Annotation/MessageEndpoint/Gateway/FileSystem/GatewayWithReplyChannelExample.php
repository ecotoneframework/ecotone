<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\FileSystem;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;

interface GatewayWithReplyChannelExample
{
    /**
     * @param string $orderId
     * @return bool
     *
     * @MessageGateway(
     *      requestChannel="requestChannel",
     *      parameterConverters={
     *          @Payload(parameterName="orderId")
     *      },
     *      requiredInterceptorNames={"dbalTransaction"}
     * )
     */
    public function buy(string $orderId): bool;
}