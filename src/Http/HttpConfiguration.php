<?php
declare(strict_types=1);

namespace Ecotone\Http;

/**
 * Class HttpConfiguration
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HttpConfiguration
{
    /**
     * @var ContentTypeMapping[]
     */
    private $contentTypeMappings;

    private $uploadFileMover;
}