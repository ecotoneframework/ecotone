<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface InboundHttpGateway
 * @package SimplyCodedSoftware\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InboundHttpGateway
{
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function execute(RequestInterface $request) : ResponseInterface;
}