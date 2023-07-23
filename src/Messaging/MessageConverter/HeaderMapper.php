<?php

declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Conversion\ConversionService;

/**
 * Interface HeaderMapper
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface HeaderMapper
{
    /**
     * @param array $headersToBeMapped
     * @return array
     */
    public function mapToMessageHeaders(array $headersToBeMapped, ConversionService $conversionService): array;

    /**
     * @param array $headersToBeMapped
     * @return array
     */
    public function mapFromMessageHeaders(array $headersToBeMapped, ConversionService $conversionService): array;
}
