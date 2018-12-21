<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface HttpRequestHandlingMessagingGateway
 * @package SimplyCodedSoftware\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface HttpGateway
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function execute(ServerRequestInterface $request) : ResponseInterface;
}