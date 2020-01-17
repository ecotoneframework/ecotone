<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

class ConversionInterceptor
{
    public const PRECEDENCE = 1000000;

    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var MediaType
     */
    private $replyContentType;


    public function __construct(ConversionService $conversionService, InterfaceToCall $interfaceToCall, MediaType $replyContentType)
    {
        $this->interfaceToCall = $interfaceToCall;
        $this->conversionService = $conversionService;
        $this->replyContentType = $replyContentType;
    }

    public function handle(MethodInvocation $methodInvocation)
    {
        $result = $methodInvocation->proceed();

        if (is_null($result)) {
            return null;
        }

        $data = $result instanceof Message ? $result->getPayload() : $result;
        $data = $this->conversionService->convert(
            $data,
            TypeDescriptor::createFromVariable($data),
            MediaType::createApplicationXPHPObject(),
            TypeDescriptor::createAnythingType(),
            $this->replyContentType
        );

        if ($result instanceof Message) {
            return MessageBuilder::fromMessage($result)
                        ->setContentType($this->replyContentType)
                        ->setPayload($data)
                        ->build();
        }

        return $data;
    }
}