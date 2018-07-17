<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayParameterConverterBuilder
{
    /**
     * @return GatewayParameterConverter
     */
    public function build() : GatewayParameterConverter;
}