<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface PropertySetterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher
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