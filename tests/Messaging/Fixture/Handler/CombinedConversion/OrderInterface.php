<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;

/**
 * licence Apache-2.0
 */
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
