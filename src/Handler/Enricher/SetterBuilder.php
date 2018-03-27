<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface PropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface SetterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return Setter
     */
    public function build(ReferenceSearchService $referenceSearchService) : Setter;
}