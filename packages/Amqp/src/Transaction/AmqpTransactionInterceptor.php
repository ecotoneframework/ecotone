<?php

namespace Ecotone\Amqp\Transaction;

use AMQPChannel;
use Ecotone\Amqp\AmqpPublisherConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Throwable;

/**
 * https://www.rabbitmq.com/blog/2011/02/10/introducing-publisher-confirms/
 *
 * The confirm.select method enables publisher confirms on a channel.Â Â Note that a transactional channel cannot be put into confirm mode and a confirm mode channel cannot be made transactional.
 */
class AmqpTransactionInterceptor
{
    /**
     * @var string[]
     */
    private $connectionReferenceNames;

    private bool $isRunningTransaction = false;

    public function __construct(array $connectionReferenceNames)
    {
        $this->connectionReferenceNames = $connectionReferenceNames;
    }

    public function transactional(MethodInvocation $methodInvocation, ?AmqpTransaction $amqpTransaction, ReferenceSearchService $referenceSearchService)
    {
        ;
        /** @var CachedConnectionFactory[] $connectionFactories */
        $connectionFactories = array_map(function (string $connectionReferenceName) use ($referenceSearchService) {
            return CachedConnectionFactory::createFor(new AmqpPublisherConnectionFactory($referenceSearchService->get($connectionReferenceName)));
        }, $amqpTransaction ? $amqpTransaction->connectionReferenceNames : $this->connectionReferenceNames);

        if ($this->isRunningTransaction) {
            return $methodInvocation->proceed();
        }

        try {
            $this->isRunningTransaction = true;
            foreach ($connectionFactories as $connectionFactory) {
                $connectionFactory->createContext()->getExtChannel()->startTransaction();
            }
            try {
                $result = $methodInvocation->proceed();

                foreach ($connectionFactories as $connectionFactory) {
                    $connectionFactory->createContext()->getExtChannel()->commitTransaction();
                }
            } catch (Throwable $exception) {
                foreach ($connectionFactories as $connectionFactory) {
                    /** @var AMQPChannel $extChannel */
                    $extChannel = $connectionFactory->createContext()->getExtChannel();
                    try {
                        $extChannel->rollbackTransaction();
                    } catch (\Throwable) {
                    }
                    $extChannel->close(); // Has to be closed in amqp_lib, as if channel is trarnsactional does not allow for sending outside of transaction
                }

                throw $exception;
            }
        } catch (Throwable $exception) {
            $this->isRunningTransaction = false;

            throw $exception;
        }

        $this->isRunningTransaction = false;
        return $result;
    }
}
