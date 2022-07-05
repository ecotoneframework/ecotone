<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayParameterConverterBuilder
{
    public function build(ReferenceSearchService $referenceSearchService) : GatewayParameterConverter;

    public function isHandling(InterfaceParameter $parameter) : bool;
}