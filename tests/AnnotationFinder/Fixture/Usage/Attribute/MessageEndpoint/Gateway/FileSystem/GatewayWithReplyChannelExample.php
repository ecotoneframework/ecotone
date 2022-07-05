<?php
declare(strict_types=1);

namespace Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Gateway\FileSystem;

use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;

#[MessageEndpoint]
interface GatewayWithReplyChannelExample
{
    #[SomeGatewayExample]
    public function buy(string $orderId): bool;
}