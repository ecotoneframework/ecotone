<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Interface FakeHttpMessageConverterGateway
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface FakeMessageConverterGatewayExample
{
    /**
     * @param array $some
     * @param int $amount
     * @return \stdClass
     * @Gateway(
     *     requestChannel="requestChannel",
     *     parameterConverters={
     *          @GatewayHeader(parameterName="some", headerName="some"),
     *          @GatewayPayload(parameterName="amount")
     *     }
     * )
     */
    public function execute(array $some, int $amount) : \stdClass;
}