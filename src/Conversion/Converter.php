<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion;

/**
 * Interface Converter
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Converter
{
    /**
     * @param mixed $source
     * @return mixed
     */
    public function convert($source, MethodTypeHintResolver $targetTypeHint);
}