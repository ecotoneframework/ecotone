<?php
declare(strict_types=1);

namespace Test\Ecotone\Http\Fixture\Annotation;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class InboundHttpGatewayExample
 * @package Test\Ecotone\Http\Fixture\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InboundHttpGatewayExample
{
    /**
     * @param RequestInterface $request
     * @param string $extraHeader
     * @return ResponseInterface
     */
    public function execute(RequestInterface $request, string $extraHeader) : ResponseInterface;
}