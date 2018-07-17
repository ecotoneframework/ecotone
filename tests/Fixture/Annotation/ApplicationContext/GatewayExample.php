<?php
declare(strict_types=1);

namespace Fixture\Annotation\ApplicationContext {

    use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway;
    use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;

    /**
     * Interface Gateway
     * @package Fixture\Annotation\ApplicationContext
     * @author Dariusz Gafka <dgafka.mail@gmail.com>
     * @MessageEndpoint()
     */
    interface GatewayExample
    {
        /**
         * @return string
         * @Gateway()
         */
        public function doSomething() : string;
    }
}