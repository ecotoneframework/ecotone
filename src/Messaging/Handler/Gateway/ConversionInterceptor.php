<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;
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
     * @var MediaType|null
     */
    private $replyContentType;


    public function __construct(ConversionService $conversionService, InterfaceToCall $interfaceToCall, ?MediaType $replyContentType)
    {
        $this->interfaceToCall = $interfaceToCall;
        $this->conversionService = $conversionService;
        $this->replyContentType = $replyContentType;
    }

    public function convert(MethodInvocation $methodInvocation)
    {
        /** @var Message $result */
        $result = $methodInvocation->proceed();

        if (is_null($result)) {
            return null;
        }

        $isMessage = $result instanceof Message;
        $data = $isMessage ? $result->getPayload() : $result;
        $sourceMediaType = MediaType::createApplicationXPHP();
        $sourceType = TypeDescriptor::createFromVariable($data);

//        @TODO test
//        if ($this->interfaceToCall->getReturnType()->isCompatibleWith(TypeDescriptor::create(Message::class))) {
//            if (!$isMessage) {
//                $result =  MessageBuilder::fromMessage($result)
//                    ->setContentType($this->replyContentType)
//                    ->setPayload($data)
//                    ->build();
//            }
//
//            return $result;
//        }
//        @TODO end

        if ($isMessage) {
            if ($result->getHeaders()->hasContentType()) {
                $sourceMediaType = $result->getHeaders()->getContentType();

                if ($sourceMediaType->hasTypeParameter()) {
                    $sourceType = $sourceMediaType->getTypeParameter();
                }
            }
        }

        if (!$this->replyContentType) {
            if (!$this->interfaceToCall->getReturnType()->isMessage() && !$sourceType->isCompatibleWith($this->interfaceToCall->getReturnType())) {
                if ($this->conversionService->canConvert($sourceType, $sourceMediaType, $this->interfaceToCall->getReturnType(), MediaType::createApplicationXPHP())) {
                    return $this->conversionService->convert($data, $sourceType, $sourceMediaType, $this->interfaceToCall->getReturnType(), MediaType::createApplicationXPHP());
                }
            }

            return $result;
        }

        if (!$sourceMediaType->isCompatibleWith($this->replyContentType) || ($this->replyContentType->hasTypeParameter() && $this->replyContentType->getTypeParameter()->isIterable())) {
            $targetType = $this->replyContentType->hasTypeParameter() ? $this->replyContentType->getTypeParameter() : TypeDescriptor::createAnythingType();
            if (!$this->conversionService->canConvert(
                $sourceType,
                $sourceMediaType,
                $targetType,
                $this->replyContentType
            )) {
                throw InvalidArgumentException::create("Lack of converter for {$this->interfaceToCall} can't convert reply {$sourceMediaType}:{$sourceType} to {$this->replyContentType}:{$targetType}");
            }

            $data = $this->conversionService->convert(
                $data,
                $sourceType,
                $sourceMediaType,
                $targetType,
                $this->replyContentType
            );
        }

        if ($result instanceof Message) {
            return MessageBuilder::fromMessage($result)
                        ->setContentType($this->replyContentType)
                        ->setPayload($data)
                        ->build();
        }

        return $data;
    }
}