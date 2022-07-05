<?php
declare(strict_types=1);

namespace Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Gateway\FileSystem;

use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeGatewayExample;

#[MessageEndpoint]
interface GatewayWithReplyChannelExample
{
    #[SomeGatewayExample]
    public function buy(string $orderId): bool;
}