<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Gateway
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