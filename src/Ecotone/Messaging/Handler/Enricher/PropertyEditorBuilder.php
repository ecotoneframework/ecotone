<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Interface PropertySetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher
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