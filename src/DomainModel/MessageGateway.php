<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Interface MessageFlowGateway
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface MessageGateway
{
    /**
     * @param string $routeToChannelName
     * @param mixed $payload
     *
     * @return mixed For query handler it will return value, otherwise not
     *
     * @Gateway(
     *      requestChannel=AggregateMessage::AGGREGATE_SEND_MESSAGE_CHANNEL,
     *      parameterConverters={
     *          @GatewayHeader(headerName=AggregateMessage::AGGREGATE_MESSAGE_CHANNEL_NAME_TO_SEND, parameterName="routeToChannelName"),
     *          @GatewayPayload(parameterName="payload")
     *     }
     * )
     */
    public function execute(string $routeToChannelName, $payload);

    /**
     * @param string $routeToChannelName
     * @param mixed $payload
     *
     * @param string $contentType
     * @return mixed For query handler it will return value, otherwise not
     *
     * @Gateway(
     *      requestChannel=AggregateMessage::AGGREGATE_SEND_MESSAGE_CHANNEL,
     *      parameterConverters={
     *          @GatewayHeader(headerName=AggregateMessage::AGGREGATE_MESSAGE_CHANNEL_NAME_TO_SEND, parameterName="routeToChannelName"),
     *          @GatewayPayload(parameterName="payload"),
     *          @GatewayHeader(headerName=MessageHeaders::CONTENT_TYPE, parameterName="contentType")
     *     }
     * )
     */
    public function executeWithContentType(string $routeToChannelName, $payload, string $contentType);
}