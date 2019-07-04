<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface AroundInterceptorObjectBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AroundInterceptorObjectBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return object
     */
    public function build(ReferenceSearchService $referenceSearchService) : object;

    /**
     * @return string[]
     */
    public function getRequiredReferenceNames() : array;
}