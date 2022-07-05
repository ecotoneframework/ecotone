<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

class ConversionInterceptor
{
    /**
     * @param MessageConverter[] $messageConverters
     */
    public function __construct(
        private ConversionService $conversionService,
        private InterfaceToCall $interfaceToCall,
        private ?MediaType $replyContentType,
        private array $messageConverters
    ) {}

    public function convert(MethodInvocation $methodInvocation)
    {
        /** @var Message $result */
        $result = $methodInvocation->proceed();

        if (is_null($result) || !$this->interfaceToCall->canReturnValue()) {
            return null;
        }

        foreach ($this->messageConverters as $messageConverter) {
            $reply = $messageConverter->fromMessage(
                $result,
                $this->interfaceToCall->getReturnType()
            );

            if ($reply) {
                return $reply;
            }
        }

        $isMessage = $result instanceof Message;
        $data = $isMessage ? $result->getPayload() : $result;
        $sourceMediaType = MediaType::createApplicationXPHP();
        $sourceType = TypeDescriptor::createFromVariable($data);

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

            if ($result instanceof Future) {
                return $result;
            }

            if ($this->interfaceToCall->doesItReturnMessage()) {
                return $result;
            }

            return $result->getPayload();
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

        if ($this->interfaceToCall->doesItReturnMessage()) {
            return MessageBuilder::fromMessage($result)
                        ->setContentType($this->replyContentType)
                        ->setPayload($data)
                        ->build();
        }

        return $data;
    }
}