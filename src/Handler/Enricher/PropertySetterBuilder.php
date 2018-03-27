<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface PropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PropertySetterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return PropertySetter
     */
    public function build(ReferenceSearchService $referenceSearchService) : PropertySetter;
}