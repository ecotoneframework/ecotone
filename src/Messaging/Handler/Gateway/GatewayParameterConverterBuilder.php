<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
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