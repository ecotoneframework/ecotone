<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayParameterConverterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return GatewayParameterConverter
     */
    public function build(ReferenceSearchService $referenceSearchService) : GatewayParameterConverter;
}