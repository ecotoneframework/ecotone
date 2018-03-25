<?php
declare(strict_types=1);

namespace Fixture\Annotation\ApplicationContext {

    use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
    use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;

    /**
     * Interface Gateway
     * @package Fixture\Annotation\ApplicationContext
     * @author Dariusz Gafka <dgafka.mail@gmail.com>
     * @MessageEndpointAnnotation()
     */
    interface GatewayExample
    {
        /**
         * @return string
         * @GatewayAnnotation()
         */
        public function doSomething() : string;
    }
}