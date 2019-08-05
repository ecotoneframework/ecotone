<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Interface AroundInterceptorObjectBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AroundInterceptorObjectBuilder
{
    /**
     * @return string
     */
    public function getInterceptingInterfaceClassName(): string;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return object
     */
    public function build(ReferenceSearchService $referenceSearchService): object;

    /**
     * @return string[]
     */
    public function getRequiredReferenceNames(): array;
}