<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;

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
     *          @Header(parameterName="some", headerName="some"),
     *          @Payload(parameterName="amount")
     *     }
     * )
     */
    public function execute(array $some, int $amount) : \stdClass;
}