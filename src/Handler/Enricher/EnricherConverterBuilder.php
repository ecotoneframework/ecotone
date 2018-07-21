<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface PropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface EnricherConverterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return EnricherConverter
     */
    public function build(ReferenceSearchService $referenceSearchService) : EnricherConverter;
}