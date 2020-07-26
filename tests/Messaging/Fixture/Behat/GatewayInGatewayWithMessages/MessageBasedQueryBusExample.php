<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\QueryBus;
use Ecotone\Messaging\MessageHeaders;

interface MessageBasedQueryBusExample
{
    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $data
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Header(parameterName="name", headerName=QueryBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="data")
     *     }
     * )
     */
    public function convertAndSend(string $name, string $dataMediaType, $data) : Message;
}