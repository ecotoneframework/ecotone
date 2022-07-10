<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;


interface OrderInterface
{
    /**
     * @return string
     */
    public function getOrderId(): string;

    /**
     * @return string
     */
    public function getName(): string;
}