<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface Gateway
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayBuilder
{
    /**
     * Name to be registered under
     *
     * @return string
     */
    public function getReferenceName() : string;

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @return string
     */
    public function getInterfaceName() : string;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @return object
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver);
}