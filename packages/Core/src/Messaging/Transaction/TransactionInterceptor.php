<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Transaction;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;

/**
 * Class TransactionInterceptor
 * @package Ecotone\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransactionInterceptor
{
    public function transactional(MethodInvocation $methodInvocation, ReferenceSearchService $referenceSearchService, Transactional $transactional, Message $message)
    {
        /** @var TransactionFactory[] $factories */
        $factories = [];
        foreach ($transactional->getFactoryReferenceNameList() as $referenceName) {
            $factories[] = $referenceSearchService->get($referenceName);
        }

        /** @var Transaction[] $runningTransactions */
        $runningTransactions = [];
        foreach ($factories as $factory) {
            $runningTransactions[] = $factory->begin($message);
        }

        try {
            $result = $methodInvocation->proceed();
        } catch (\Throwable $throwable) {
            foreach ($runningTransactions as $runningTransaction) {
                $runningTransaction->rollback();
            }

            throw $throwable;
        }

        foreach ($runningTransactions as $runningTransaction) {
            $runningTransaction->commit();
        }

        return $result;
    }
}