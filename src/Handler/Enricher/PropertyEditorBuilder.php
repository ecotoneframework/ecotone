<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface PropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PropertyEditorBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return PropertyEditor
     */
    public function build(ReferenceSearchService $referenceSearchService) : PropertyEditor;
}